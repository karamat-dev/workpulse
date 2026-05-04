<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class TransferController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }
    }

    private function normalizeEmail(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    private function assertDistinctProfileEmails(string $officialEmail, string $personalEmail, ?int $ignoreUserId = null): void
    {
        $official = $this->normalizeEmail($officialEmail);
        $personal = $this->normalizeEmail($personalEmail);

        if ($personal === '') {
            throw ValidationException::withMessages(['personal_email' => 'Personal email is required for every employee.']);
        }

        if ($official === $personal) {
            throw ValidationException::withMessages(['personal_email' => 'Official email and personal email must be different.']);
        }

        $officialExists = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$official])
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();
        $officialAsPersonalExists = DB::table('employee_profiles')
            ->whereNotNull('personal_email')
            ->whereRaw('LOWER(personal_email) = ?', [$official])
            ->when($ignoreUserId, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->exists();
        $personalAsOfficialExists = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$personal])
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();
        $personalExists = DB::table('employee_profiles')
            ->whereNotNull('personal_email')
            ->whereRaw('LOWER(personal_email) = ?', [$personal])
            ->when($ignoreUserId, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->exists();

        if ($officialExists || $officialAsPersonalExists || $personalAsOfficialExists || $personalExists) {
            throw ValidationException::withMessages(['email' => 'Official and personal emails must be unique across all employee accounts.']);
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureAdmin($request);

        $employees = $this->employeeExportRows(true);
        $company = $this->companyExportRow();
        $departments = DB::table('departments')->orderBy('name')->get();
        $shifts = DB::table('shifts')->orderBy('name')->get();
        $attendance = DB::table('attendance_days')->orderByDesc('date')->get();
        $leaves = DB::table('leave_requests')->orderByDesc('created_at')->get();
        $announcements = DB::table('announcements')->orderByDesc('published_on')->get();
        $announcementVoteOptions = DB::table('announcement_vote_options')->orderBy('announcement_id')->orderBy('sort_order')->get();
        $announcementVotes = DB::table('announcement_votes')->orderBy('announcement_id')->orderBy('user_id')->get();
        $holidays = DB::table('holidays')->orderBy('date')->get();

        $payload = [
            'app' => 'muSharp',
            'exported_at' => now()->toIso8601String(),
            'company' => $company,
            'employees' => $employees,
            'departments' => $departments,
            'shifts' => $shifts,
            'attendance_days' => $attendance,
            'leave_requests' => $leaves,
            'announcements' => $announcements,
            'announcement_vote_options' => $announcementVoteOptions,
            'announcement_votes' => $announcementVotes,
            'holidays' => $holidays,
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, 'musharp-transfer-data.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function exportEmployees(Request $request): StreamedResponse
    {
        $this->ensureAdmin($request);

        $payload = [
            'app' => 'muSharp',
            'exported_at' => now()->toIso8601String(),
            'employees' => $this->employeeExportRows(true),
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, 'musharp-employee-profiles.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function importEmployees(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:json,csv,txt,xlsx', 'max:10240'],
            'preview' => ['nullable', 'boolean'],
            'column_mapping' => ['nullable', 'string'],
        ]);

        [$rows, $sourceColumns] = $this->employeeImportRows($validated['file']);

        if (!is_array($rows) || $rows === []) {
            $columnsMessage = $sourceColumns !== []
                ? ' Detected columns: '.implode(', ', array_slice($sourceColumns, 0, 8)).(count($sourceColumns) > 8 ? '...' : '')
                : ' No header columns were detected.';

            return response()->json([
                'ok' => false,
                'message' => 'No employee profile rows found. Make sure the first row contains column headers and employee data starts on the next row.'.$columnsMessage,
            ], 422);
        }

        if ($request->boolean('preview')) {
            return response()->json([
                'ok' => true,
                'preview' => true,
                'columns' => $sourceColumns,
                'sample' => array_slice($rows, 0, 3),
                'fields' => $this->employeeImportFieldOptions(),
                'suggested_mapping' => $this->suggestEmployeeImportMapping($sourceColumns),
            ]);
        }

        $columnMapping = $this->decodeEmployeeColumnMapping((string) ($validated['column_mapping'] ?? ''));
        if ($columnMapping !== []) {
            $rows = $this->applyEmployeeColumnMapping($rows, $columnMapping);
        }

        $imported = 0;
        $skipped = 0;
        $createdCustomFields = [];
        $customFieldIds = [];
        $knownFieldMap = $this->employeeImportFieldMap();

        DB::transaction(function () use ($rows, $knownFieldMap, &$imported, &$skipped, &$createdCustomFields, &$customFieldIds) {
            foreach ($rows as $row) {
                $row = $this->normalizeEmployeeImportRow($row, $knownFieldMap);
                $customValues = $row['_custom'] ?? [];
                foreach ($this->employeeImportCustomFieldLabels() as $field => $label) {
                    $value = $row[$field] ?? null;
                    if (trim((string) ($value ?? '')) !== '') {
                        $customValues[$label] = $value;
                    }
                }

                $employeeCode = trim((string) ($row['employee_code'] ?? $row['id'] ?? ''));
                $email = trim((string) ($row['email'] ?? ''));
                $personalEmail = trim((string) ($row['personal_email'] ?? $row['personalEmail'] ?? ''));
                $name = trim((string) ($row['name'] ?? trim(($row['fname'] ?? '').' '.($row['lname'] ?? ''))));

                if ($email === '' || $name === '') {
                    $skipped++;
                    continue;
                }

                $deptName = trim((string) ($row['department'] ?? $row['dept'] ?? 'General'));
                if ($employeeCode === '') {
                    $employeeCode = $this->nextEmployeeCodeForTeam($deptName);
                }

                $departmentId = DB::table('departments')->where('name', $deptName)->value('id');
                if (!$departmentId) {
                    $departmentId = DB::table('departments')->insertGetId([
                        'name' => $deptName,
                        'color' => '#2447D0',
                        'head_user_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $shiftId = null;
                $shiftCode = trim((string) ($row['shift_code'] ?? ''));
                $shiftName = trim((string) ($row['shift_name'] ?? $row['shiftName'] ?? ''));
                if ($shiftCode !== '' || $shiftName !== '') {
                    $shift = null;
                    if ($shiftCode !== '') {
                        $shift = DB::table('shifts')->where('code', $shiftCode)->first();
                    }
                    if (!$shift && $shiftName !== '') {
                        $shift = DB::table('shifts')->where('name', $shiftName)->first();
                    }
                    if (!$shift) {
                        $shiftId = DB::table('shifts')->insertGetId([
                            'code' => $shiftCode !== '' ? $shiftCode : Str::of($shiftName)->lower()->slug('_')->value(),
                            'name' => $shiftName !== '' ? $shiftName : 'Imported Shift',
                            'start_time' => (($row['shift_start'] ?? $row['shiftStart'] ?? '11:00').':00'),
                            'end_time' => (($row['shift_end'] ?? $row['shiftEnd'] ?? '20:00').':00'),
                            'grace_minutes' => (int) ($row['shift_grace_minutes'] ?? $row['shiftGrace'] ?? 10),
                            'working_days' => $row['shift_working_days'] ?? $row['shiftWorkingDays'] ?? 'Mon-Fri',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $shiftId = $shift->id;
                    }
                }

                $managerName = trim((string) ($row['manager_name'] ?? $row['manager'] ?? ''));
                $managerUserId = $managerName !== ''
                    ? DB::table('users')->where('name', $managerName)->value('id')
                    : null;

                $existingUser = DB::table('users')
                    ->where('employee_code', $employeeCode)
                    ->orWhere('email', $email)
                    ->first();

                $userId = $existingUser?->id;
                if ($personalEmail === '' && $userId) {
                    $personalEmail = (string) DB::table('employee_profiles')->where('user_id', $userId)->value('personal_email');
                }
                if ($personalEmail === '') {
                    $personalEmail = $this->importPlaceholderPersonalEmail($employeeCode, $email);
                }

                $this->assertDistinctProfileEmails($email, $personalEmail, $userId ? (int) $userId : null);

                if ($userId) {
                    DB::table('users')->where('id', $userId)->update([
                        'name' => $name,
                        'email' => $email,
                        'employee_code' => $employeeCode,
                        'role' => $row['role'] ?? $existingUser->role ?? 'employee',
                        'updated_at' => now(),
                    ]);
                } else {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $name,
                        'email' => $email,
                        'employee_code' => $employeeCode,
                        'role' => $row['role'] ?? 'employee',
                        'password' => Hash::make(Str::random(16)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('employee_profiles')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'department_id' => $departmentId,
                        'manager_user_id' => $managerUserId,
                        'shift_id' => $shiftId,
                        'designation' => $row['designation'] ?? $row['desg'] ?? null,
                        'date_of_joining' => $this->importDate($row['date_of_joining'] ?? $row['doj'] ?? null),
                        'probation_end_date' => $this->importDate($row['probation_end_date'] ?? $row['dop'] ?? null),
                        'last_working_date' => $this->importDate($row['last_working_date'] ?? $row['lwd'] ?? null),
                        'employment_type' => $row['employment_type'] ?? $row['type'] ?? null,
                        'status' => $row['status'] ?? 'Active',
                        'date_of_birth' => $this->importDate($row['date_of_birth'] ?? $row['dob'] ?? null),
                        'gender' => $row['gender'] ?? null,
                        'cnic' => $row['cnic'] ?? null,
                        'cnic_document_path' => null,
                        'cnic_document_name' => null,
                        'personal_phone' => $row['personal_phone'] ?? $row['phone'] ?? null,
                        'personal_email' => $personalEmail,
                        'address' => $row['address'] ?? null,
                        'blood_group' => $row['blood_group'] ?? $row['blood'] ?? null,
                        'next_of_kin_name' => $row['next_of_kin_name'] ?? $row['kin'] ?? null,
                        'next_of_kin_relationship' => $row['next_of_kin_relationship'] ?? $row['kinRel'] ?? null,
                        'next_of_kin_phone' => $row['next_of_kin_phone'] ?? $row['kinPhone'] ?? null,
                        'basic_salary' => $row['basic_salary'] ?? $row['basic'] ?? null,
                        'house_allowance' => $row['house_allowance'] ?? $row['house'] ?? null,
                        'transport_allowance' => $row['transport_allowance'] ?? $row['transport'] ?? null,
                        'tax_deduction' => $row['tax_deduction'] ?? $row['tax'] ?? null,
                        'bank_name' => $row['bank_name'] ?? $row['bank'] ?? null,
                        'bank_account_no' => $row['bank_account_no'] ?? $row['acct'] ?? null,
                        'bank_iban' => $row['bank_iban'] ?? $row['iban'] ?? null,
                        'work_location' => $row['work_location'] ?? $row['workLocation'] ?? null,
                        'confirmation_date' => $this->importDate($row['confirmation_date'] ?? $row['confirmationDate'] ?? null),
                        'marital_status' => $row['marital_status'] ?? $row['maritalStatus'] ?? null,
                        'passport_no' => $row['passport_no'] ?? $row['passportNo'] ?? null,
                        'pay_period' => $row['pay_period'] ?? $row['payPeriod'] ?? null,
                        'salary_start_date' => $this->importDate($row['salary_start_date'] ?? $row['salaryStartDate'] ?? null),
                        'contribution_amount' => $row['contribution_amount'] ?? $row['contribution'] ?? null,
                        'other_deductions' => $row['other_deductions'] ?? $row['otherDeductions'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('reporting_lines')->updateOrInsert(
                    ['user_id' => $userId],
                    [
                        'manager_user_id' => $managerUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                foreach ($customValues as $label => $value) {
                    $fieldId = $this->employeeCustomFieldId($label, $customFieldIds, $createdCustomFields);
                    DB::table('employee_custom_field_values')->updateOrInsert(
                        ['user_id' => $userId, 'field_id' => $fieldId],
                        [
                            'value' => $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                $imported++;
            }
        });

        return response()->json([
            'ok' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'columns' => $sourceColumns,
            'created_fields' => array_values(array_unique($createdCustomFields)),
        ]);
    }

    public function exportCompany(Request $request): StreamedResponse
    {
        $this->ensureAdmin($request);

        $payload = [
            'app' => 'muSharp',
            'exported_at' => now()->toIso8601String(),
            'company' => $this->companyExportRow(),
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, 'musharp-company-details.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function importCompany(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:5120'],
        ]);

        $payload = json_decode(file_get_contents($validated['file']->getRealPath()), true);
        $row = $payload['company'] ?? ($payload['data']['company'] ?? null);

        if (!is_array($row) || $row === []) {
            return response()->json(['ok' => false, 'message' => 'No company details found in import file.'], 422);
        }

        DB::table('company_settings')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => $row['company_name'] ?? $row['companyName'] ?? null,
                'website_link' => $row['website_link'] ?? $row['website'] ?? null,
                'official_email' => $row['official_email'] ?? $row['officialEmail'] ?? null,
                'official_contact_no' => $row['official_contact_no'] ?? $row['officialContactNo'] ?? null,
                'office_location' => $row['office_location'] ?? $row['officeLocation'] ?? null,
                'linkedin_page' => $row['linkedin_page'] ?? $row['linkedinPage'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Company details imported successfully.']);
    }

    private function employeeExportRows(bool $includeConfidential): array
    {
        $select = [
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as department',
            'employee_profiles.designation',
            'employee_profiles.date_of_joining',
            'employee_profiles.probation_end_date',
            'employee_profiles.last_working_date',
            'employee_profiles.employment_type',
            'employee_profiles.status',
            'employee_profiles.work_location',
            'employee_profiles.confirmation_date',
            'employee_profiles.date_of_birth',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.cnic_document_path',
            'employee_profiles.cnic_document_name',
            'employee_profiles.passport_no',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'employee_profiles.address',
            'employee_profiles.marital_status',
            'employee_profiles.blood_group',
            'employee_profiles.next_of_kin_name',
            'employee_profiles.next_of_kin_relationship',
            'employee_profiles.next_of_kin_phone',
            'mgr.name as manager_name',
            'shifts.code as shift_code',
            'shifts.name as shift_name',
            'shifts.start_time as shift_start',
            'shifts.end_time as shift_end',
            'shifts.grace_minutes as shift_grace_minutes',
            'shifts.working_days as shift_working_days',
        ];

        if ($includeConfidential) {
            $select = array_merge($select, [
                'employee_profiles.basic_salary',
                'employee_profiles.house_allowance',
                'employee_profiles.transport_allowance',
                'employee_profiles.pay_period',
                'employee_profiles.salary_start_date',
                'employee_profiles.contribution_amount',
                'employee_profiles.other_deductions',
                'employee_profiles.tax_deduction',
                'employee_profiles.bank_name',
                'employee_profiles.bank_account_no',
                'employee_profiles.bank_iban',
            ]);
        }

        $rows = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->select($select)
            ->orderBy('users.employee_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $customValues = DB::table('employee_custom_field_values')
            ->join('employee_custom_fields', 'employee_custom_fields.id', '=', 'employee_custom_field_values.field_id')
            ->join('users', 'users.id', '=', 'employee_custom_field_values.user_id')
            ->select(['users.employee_code', 'employee_custom_fields.label', 'employee_custom_field_values.value'])
            ->get()
            ->groupBy('employee_code');

        return array_map(function (array $row) use ($customValues) {
            $custom = $customValues->get($row['employee_code'] ?? '', collect());
            foreach ($custom as $fieldValue) {
                $row[$fieldValue->label] = $fieldValue->value;
            }

            return $row;
        }, $rows);
    }

    private function employeeImportRows($file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            $payload = json_decode(file_get_contents($file->getRealPath()), true);
            $rows = $payload['employees'] ?? ($payload['data']['employees'] ?? null);
            $columns = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];

            return [$rows, $columns];
        }

        if ($extension === 'xlsx') {
            return $this->xlsxRows($file->getRealPath());
        }

        return $this->csvRows($file->getRealPath());
    }

    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [[], []];
        }

        $delimiter = $this->detectCsvDelimiter($path);
        $headers = fgetcsv($handle, 0, $delimiter) ?: [];
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $rows = [];
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = $values[$index] ?? null;
            }
            if (array_filter($row, fn ($value) => trim((string) $value) !== '') !== []) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        return [$rows, $headers];
    }

    private function detectCsvDelimiter(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $firstLine = strtok($sample, "\r\n") ?: $sample;
        $delimiters = [',', ';', "\t", '|'];
        $scores = [];

        foreach ($delimiters as $delimiter) {
            $scores[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($scores);
        $delimiter = (string) array_key_first($scores);

        return ($scores[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function xlsxRows(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'XLSX import requires the PHP zip extension.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'Unable to open XLSX file.']);
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            foreach ($shared->si ?? [] as $si) {
                $parts = [];
                if (isset($si->t)) {
                    $parts[] = (string) $si->t;
                }
                foreach ($si->r ?? [] as $run) {
                    $parts[] = (string) $run->t;
                }
                $sharedStrings[] = implode('', $parts);
            }
        }

        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        $zip->close();
        if ($sheetXml === false) {
            return [[], []];
        }

        $sheet = simplexml_load_string($sheetXml);
        $grid = [];
        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            foreach ($row->c ?? [] as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                preg_match('/([A-Z]+)/', $ref, $matches);
                $index = $this->xlsxColumnIndex($matches[1] ?? 'A');
                $type = (string) ($cell['t'] ?? '');
                $value = (string) ($cell->v ?? '');
                $cells[$index] = match ($type) {
                    's' => $sharedStrings[(int) $value] ?? '',
                    'inlineStr' => (string) ($cell->is->t ?? ''),
                    'str' => (string) ($cell->v ?? ''),
                    'b' => $value === '1' ? '1' : '0',
                    default => $value,
                };
            }
            if ($cells !== []) {
                ksort($cells);
                $grid[] = $cells;
            }
        }

        $headers = array_values(array_map(fn ($header) => trim((string) $header), $grid[0] ?? []));
        $rows = [];
        foreach (array_slice($grid, 1) as $values) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = $values[$index] ?? null;
            }
            if (array_filter($row, fn ($value) => trim((string) $value) !== '') !== []) {
                $rows[] = $row;
            }
        }

        return [$rows, $headers];
    }

    private function firstWorksheetPath(ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml !== false && $relsXml !== false) {
            $workbook = simplexml_load_string($workbookXml);
            $relationships = simplexml_load_string($relsXml);
            $relationshipMap = [];

            foreach ($relationships->Relationship ?? [] as $relationship) {
                $id = (string) ($relationship['Id'] ?? '');
                $target = (string) ($relationship['Target'] ?? '');
                if ($id !== '' && $target !== '') {
                    $relationshipMap[$id] = str_starts_with($target, '/')
                        ? ltrim($target, '/')
                        : 'xl/'.ltrim($target, '/');
                }
            }

            $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $sheets = $workbook->xpath('//sheet') ?: [];
            foreach ($sheets as $sheet) {
                $attributes = $sheet->attributes('r', true);
                $relationshipId = (string) ($attributes['id'] ?? '');
                if ($relationshipId !== '' && isset($relationshipMap[$relationshipId])) {
                    return $relationshipMap[$relationshipId];
                }
            }
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                return $name;
            }
        }

        return null;
    }

    private function xlsxColumnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeEmployeeImportRow(array $row, array $fieldMap): array
    {
        $normalized = ['_custom' => []];
        foreach ($row as $header => $value) {
            $header = trim((string) $header);
            if ($header === '') {
                continue;
            }

            $key = $this->normalizeImportHeader($header);
            $mapped = $fieldMap[$key] ?? null;
            if ($mapped) {
                $normalized[$mapped] = is_string($value) ? trim($value) : $value;
            } else {
                $normalized['_custom'][$header] = is_scalar($value) || $value === null
                    ? trim((string) $value)
                    : json_encode($value);
            }
        }

        return $normalized;
    }

    private function decodeEmployeeColumnMapping(string $mappingJson): array
    {
        if (trim($mappingJson) === '') {
            return [];
        }

        $mapping = json_decode($mappingJson, true);
        if (!is_array($mapping)) {
            throw ValidationException::withMessages(['column_mapping' => 'Column mapping must be valid JSON.']);
        }

        $allowedFields = array_column($this->employeeImportFieldOptions(), 'value');
        $allowed = array_fill_keys($allowedFields, true);
        $allowed['__skip'] = true;
        $allowed['__custom'] = true;

        $clean = [];
        foreach ($mapping as $source => $target) {
            $source = trim((string) $source);
            $target = trim((string) $target);
            if ($source === '' || $target === '' || !isset($allowed[$target])) {
                continue;
            }
            $clean[$source] = $target;
        }

        return $clean;
    }

    private function applyEmployeeColumnMapping(array $rows, array $mapping): array
    {
        return array_map(function (array $row) use ($mapping) {
            $mappedRow = [];
            foreach ($mapping as $source => $target) {
                if (!array_key_exists($source, $row) || $target === '__skip') {
                    continue;
                }

                $mappedRow[$target === '__custom' ? $source : $target] = $row[$source];
            }

            return $mappedRow;
        }, $rows);
    }

    private function suggestEmployeeImportMapping(array $columns): array
    {
        $knownFieldMap = $this->employeeImportFieldMap();
        $suggestions = [];
        foreach ($columns as $column) {
            $key = $this->normalizeImportHeader((string) $column);
            $suggestions[$column] = $knownFieldMap[$key] ?? '__custom';
        }

        return $suggestions;
    }

    private function employeeImportFieldOptions(): array
    {
        return [
            ['value' => 'employee_code', 'label' => 'Employee Code'],
            ['value' => 'name', 'label' => 'Full Name'],
            ['value' => 'fname', 'label' => 'First Name'],
            ['value' => 'lname', 'label' => 'Last Name'],
            ['value' => 'email', 'label' => 'Official Email'],
            ['value' => 'personal_email', 'label' => 'Personal Email'],
            ['value' => 'role', 'label' => 'Role'],
            ['value' => 'department', 'label' => 'Team / Department'],
            ['value' => 'designation', 'label' => 'Designation'],
            ['value' => 'date_of_joining', 'label' => 'Hired Date / Date of Joining'],
            ['value' => 'probation_end_date', 'label' => 'Probation End Date'],
            ['value' => 'last_working_date', 'label' => 'Last Working Date'],
            ['value' => 'employment_type', 'label' => 'Employment Type'],
            ['value' => 'status', 'label' => 'Status'],
            ['value' => 'date_of_birth', 'label' => 'Date of Birth'],
            ['value' => 'age', 'label' => 'Age'],
            ['value' => 'gender', 'label' => 'Gender'],
            ['value' => 'cnic', 'label' => 'CNIC / National ID'],
            ['value' => 'personal_phone', 'label' => 'Personal Phone'],
            ['value' => 'work_phone', 'label' => 'Work Phone'],
            ['value' => 'address', 'label' => 'Address'],
            ['value' => 'blood_group', 'label' => 'Blood Group'],
            ['value' => 'next_of_kin_name', 'label' => 'Emergency Contact / Next of Kin Name'],
            ['value' => 'next_of_kin_relationship', 'label' => 'Next of Kin Relationship'],
            ['value' => 'next_of_kin_phone', 'label' => 'Emergency Phone / Next of Kin Phone'],
            ['value' => 'manager_name', 'label' => 'Manager Name'],
            ['value' => 'shift_code', 'label' => 'Shift Code'],
            ['value' => 'shift_name', 'label' => 'Shift Name'],
            ['value' => 'shift_start', 'label' => 'Shift Start'],
            ['value' => 'shift_end', 'label' => 'Shift End'],
            ['value' => 'basic_salary', 'label' => 'Basic Salary'],
            ['value' => 'house_allowance', 'label' => 'House Allowance'],
            ['value' => 'transport_allowance', 'label' => 'Transport Allowance'],
            ['value' => 'tax_deduction', 'label' => 'Tax Deduction'],
            ['value' => 'bank_name', 'label' => 'Bank Name'],
            ['value' => 'bank_branch', 'label' => 'Branch'],
            ['value' => 'bank_branch_code', 'label' => 'Branch Code'],
            ['value' => 'bank_account_no', 'label' => 'Bank Account No'],
            ['value' => 'bank_iban', 'label' => 'Bank IBAN'],
            ['value' => 'work_location', 'label' => 'Work Location'],
            ['value' => 'confirmation_date', 'label' => 'Confirmation Date'],
            ['value' => 'marital_status', 'label' => 'Marital Status'],
            ['value' => 'passport_no', 'label' => 'Passport No'],
            ['value' => 'pay_period', 'label' => 'Pay Period'],
            ['value' => 'salary_start_date', 'label' => 'Salary Start Date'],
            ['value' => 'contribution_amount', 'label' => 'Contribution Amount'],
            ['value' => 'other_deductions', 'label' => 'Other Deductions'],
        ];
    }

    private function employeeImportCustomFieldLabels(): array
    {
        return [
            'age' => 'Age',
            'work_phone' => 'Work Phone',
            'bank_branch' => 'Branch',
            'bank_branch_code' => 'Branch Code',
        ];
    }

    private function employeeImportFieldMap(): array
    {
        $aliases = [
            'employee_code' => ['employee code', 'employee id', 'emp id', 'emp code', 'id', 'code'],
            'name' => ['name', 'full name', 'employee name'],
            'fname' => ['first name', 'fname', 'given name'],
            'lname' => ['last name', 'lname', 'surname', 'family name'],
            'email' => ['email', 'official email', 'work email', 'company email'],
            'personal_email' => ['personal email', 'private email'],
            'role' => ['role', 'user role'],
            'department' => ['department', 'dept', 'team'],
            'designation' => ['designation', 'job title', 'title', 'position'],
            'date_of_joining' => ['date of joining', 'joining date', 'doj', 'hire date', 'hired date'],
            'probation_end_date' => ['probation end date', 'probation date', 'dop'],
            'last_working_date' => ['last working date', 'lwd', 'exit date'],
            'employment_type' => ['employment type', 'type'],
            'status' => ['status', 'employee status'],
            'date_of_birth' => ['date of birth', 'dob', 'birth date'],
            'age' => ['age'],
            'gender' => ['gender'],
            'cnic' => ['cnic', 'national id', 'national id / cnic'],
            'personal_phone' => ['phone', 'personal phone', 'mobile', 'mobile number', 'contact number', 'home phone'],
            'work_phone' => ['work phone'],
            'address' => ['address'],
            'blood_group' => ['blood group', 'blood'],
            'next_of_kin_name' => ['next of kin', 'next of kin name', 'kin', 'emergency contact'],
            'next_of_kin_relationship' => ['kin relationship', 'next of kin relationship', 'kin relation'],
            'next_of_kin_phone' => ['kin phone', 'next of kin phone', 'emergency phone'],
            'manager_name' => ['manager', 'line manager', 'reporting manager', 'manager name'],
            'shift_code' => ['shift code'],
            'shift_name' => ['shift', 'shift name'],
            'shift_start' => ['shift start', 'start time'],
            'shift_end' => ['shift end', 'end time'],
            'basic_salary' => ['basic salary', 'basic'],
            'house_allowance' => ['house allowance', 'house'],
            'transport_allowance' => ['transport allowance', 'transport'],
            'tax_deduction' => ['tax deduction', 'tax'],
            'bank_name' => ['bank name', 'bank'],
            'bank_branch' => ['branch', 'bank branch'],
            'bank_branch_code' => ['branch code', 'bank branch code'],
            'bank_account_no' => ['bank account no', 'account no', 'account number', 'acct'],
            'bank_iban' => ['iban', 'bank iban'],
            'work_location' => ['work location', 'office location', 'location'],
            'confirmation_date' => ['confirmation date'],
            'marital_status' => ['marital status'],
            'passport_no' => ['passport no', 'passport number'],
            'pay_period' => ['pay period'],
            'salary_start_date' => ['salary start date'],
            'contribution_amount' => ['contribution', 'contribution amount'],
            'other_deductions' => ['other deductions'],
        ];

        $map = [];
        foreach ($aliases as $field => $headers) {
            foreach ($headers as $header) {
                $map[$this->normalizeImportHeader($header)] = $field;
            }
        }

        return $map;
    }

    private function normalizeImportHeader(string $header): string
    {
        return Str::of($header)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();
    }

    private function importDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (is_numeric($value) && (float) $value > 0) {
            return now()->create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString();
        }

        $formats = [
            'd/m/Y',
            'd-m-Y',
            'm/d/Y',
            'm-d-Y',
            'd/m/y',
            'd-m-y',
            'm/d/y',
            'm-d-y',
            'd M Y',
            'd F Y',
            'M d Y',
            'F d Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                $errors = \Carbon\Carbon::getLastErrors();
                if ($date && ($errors === false || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                // Try the next known import date format.
            }
        }

        try {
            return now()->parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function employeeCustomFieldId(string $label, array &$cache, array &$createdLabels): int
    {
        $key = Str::of($label)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
        $key = $key !== '' ? $key : 'imported_field';

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $existing = DB::table('employee_custom_fields')->where('key', $key)->first();
        if ($existing) {
            return $cache[$key] = (int) $existing->id;
        }

        $createdLabels[] = $label;

        return $cache[$key] = DB::table('employee_custom_fields')->insertGetId([
            'key' => $key,
            'label' => $label,
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importPlaceholderPersonalEmail(string $employeeCode, string $officialEmail): string
    {
        $domain = Str::after($officialEmail, '@') ?: 'import.local';
        $local = Str::of($employeeCode ?: Str::before($officialEmail, '@'))->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->value();

        return 'personal+'.($local ?: Str::random(8)).'@'.$domain;
    }

    private function nextEmployeeCodeForTeam(string $teamName): string
    {
        $prefix = $this->teamPrefix($teamName);
        $existing = DB::table('users')->where('employee_code', 'like', $prefix.'-emp%')->pluck('employee_code');
        $max = 0;
        foreach ($existing as $code) {
            if (preg_match('/^'.preg_quote($prefix, '/').'\-emp(\d+)$/i', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix.'-emp'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function teamPrefix(string $teamName): string
    {
        $normalized = Str::of($teamName)->trim()->lower()->replaceMatches('/[^a-z0-9\s]+/', '')->value();
        $mapped = [
            'development' => 'Dev',
            'engineering' => 'Eng',
            'human resources' => 'HR',
            'hr' => 'HR',
            'finance' => 'Fin',
            'marketing' => 'Mkt',
            'product' => 'Prd',
            'operations' => 'Ops',
            'design' => 'Dsg',
            'support' => 'Sup',
            'sales' => 'Sal',
            'quality assurance' => 'QA',
            'qa' => 'QA',
        ][$normalized] ?? null;

        if ($mapped) {
            return $mapped;
        }

        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) > 1) {
            return strtoupper(implode('', array_map(static fn (string $word): string => substr($word, 0, 1), array_slice($words, 0, 3)))) ?: 'Tem';
        }

        $base = ucfirst(substr($normalized ?: 'team', 0, 3));

        return strlen($base) >= 2 ? $base : 'Tem';
    }

    private function companyExportRow(): array
    {
        $company = DB::table('company_settings')->where('id', 1)->first();

        return [
            'company_name' => $company?->company_name,
            'website_link' => $company?->website_link,
            'official_email' => $company?->official_email,
            'official_contact_no' => $company?->official_contact_no,
            'office_location' => $company?->office_location,
            'linkedin_page' => $company?->linkedin_page,
        ];
    }
}
