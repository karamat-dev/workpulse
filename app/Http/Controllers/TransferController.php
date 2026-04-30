<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
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
            'file' => ['required', 'file', 'mimes:json', 'max:10240'],
        ]);

        $payload = json_decode(file_get_contents($validated['file']->getRealPath()), true);
        $rows = $payload['employees'] ?? ($payload['data']['employees'] ?? null);

        if (!is_array($rows) || $rows === []) {
            return response()->json(['ok' => false, 'message' => 'No employee profiles found in import file.'], 422);
        }

        $imported = 0;

        DB::transaction(function () use ($rows, &$imported) {
            foreach ($rows as $row) {
                $employeeCode = trim((string) ($row['employee_code'] ?? $row['id'] ?? ''));
                $email = trim((string) ($row['email'] ?? ''));
                $name = trim((string) ($row['name'] ?? trim(($row['fname'] ?? '').' '.($row['lname'] ?? ''))));

                if ($employeeCode === '' || $email === '' || $name === '') {
                    continue;
                }

                $deptName = trim((string) ($row['department'] ?? $row['dept'] ?? 'General'));
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
                        'date_of_joining' => $row['date_of_joining'] ?? $row['doj'] ?? null,
                        'probation_end_date' => $row['probation_end_date'] ?? $row['dop'] ?? null,
                        'last_working_date' => $row['last_working_date'] ?? $row['lwd'] ?? null,
                        'employment_type' => $row['employment_type'] ?? $row['type'] ?? null,
                        'status' => $row['status'] ?? 'Active',
                        'date_of_birth' => $row['date_of_birth'] ?? $row['dob'] ?? null,
                        'gender' => $row['gender'] ?? null,
                        'cnic' => $row['cnic'] ?? null,
                        'cnic_document_path' => null,
                        'cnic_document_name' => null,
                        'personal_phone' => $row['personal_phone'] ?? $row['phone'] ?? null,
                        'personal_email' => $row['personal_email'] ?? $email,
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

                $imported++;
            }
        });

        return response()->json(['ok' => true, 'imported' => $imported]);
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
            'employee_profiles.date_of_birth',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.cnic_document_path',
            'employee_profiles.cnic_document_name',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'employee_profiles.address',
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
                'employee_profiles.tax_deduction',
                'employee_profiles.bank_name',
                'employee_profiles.bank_account_no',
                'employee_profiles.bank_iban',
            ]);
        }

        return DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->select($select)
            ->orderBy('users.employee_code')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
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
