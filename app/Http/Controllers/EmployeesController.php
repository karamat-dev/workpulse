<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeesController extends Controller
{
    public function show(Request $request, string $employeeCode): JsonResponse
    {
        $user = $request->user();
        $includeConfidential = $user->role === 'admin';
        $record = $this->employeeQuery($includeConfidential)
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'ok' => true,
            'employee' => $this->formatEmployeeRecord($record, $includeConfidential),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:80'],
            'lname' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'dept' => ['required', 'string', 'max:255'],
            'desg' => ['required', 'string', 'max:255'],
            'doj' => ['required', 'date_format:Y-m-d'],
            'dop' => ['nullable', 'date_format:Y-m-d'],
            'lwd' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'manager' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:20'], // default employee
            'cnic_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'basic' => ['nullable', 'integer', 'min:0'],
            'house' => ['nullable', 'integer', 'min:0'],
            'transport' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'bank' => ['nullable', 'string', 'max:255'],
            'acct' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ]);

        $role = in_array(($validated['role'] ?? 'employee'), ['employee', 'hr', 'admin'], true)
            ? ($validated['role'] ?? 'employee')
            : 'employee';

        $departmentId = DB::table('departments')->where('name', $validated['dept'])->value('id');
        if (!$departmentId) {
            $departmentId = DB::table('departments')->insertGetId([
                'name' => $validated['dept'],
                'color' => '#2447D0',
                'head_user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $managerUserId = null;
        if (!empty($validated['manager'])) {
            $managerUserId = DB::table('users')->where('name', $validated['manager'])->value('id');
        }

        $employeeCode = 'EMP-'.str_pad((string) (DB::table('users')->whereNotNull('employee_code')->count() + 1), 3, '0', STR_PAD_LEFT);
        $name = trim($validated['fname'].' '.$validated['lname']);

        $createdPassword = null;
        $cnicDocument = $request->file('cnic_document');

        $userId = DB::transaction(function () use ($validated, $role, $employeeCode, $name, $departmentId, $managerUserId, &$createdPassword, $cnicDocument) {
            $existing = DB::table('users')->where('email', $validated['email'])->first();
            if ($existing) {
                // Update existing account to become an employee profile if needed
                DB::table('users')->where('id', $existing->id)->update([
                    'name' => $name,
                    'role' => $existing->role ?: $role,
                    'employee_code' => $existing->employee_code ?: $employeeCode,
                    'password' => !empty($validated['password']) ? Hash::make($validated['password']) : $existing->password,
                    'updated_at' => now(),
                ]);

                $userId = $existing->id;
            } else {
                // Admin can define the employee password, otherwise generate one for first login.
                $tmpPassword = $validated['password'] ?? Str::random(10);
                $createdPassword = $tmpPassword;

                $userId = DB::table('users')->insertGetId([
                    'name' => $name,
                    'email' => $validated['email'],
                    'password' => Hash::make($tmpPassword),
                    'role' => $role,
                    'employee_code' => $employeeCode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $cnicDocumentMeta = $this->storeEmployeeDocument($cnicDocument, $employeeCode, $userId);

            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'department_id' => $departmentId,
                    'manager_user_id' => $managerUserId,
                    'designation' => $validated['desg'],
                    'date_of_joining' => $validated['doj'],
                    'probation_end_date' => $validated['dop'] ?? (($validated['type'] ?? '') === 'Probation'
                        ? now()->parse($validated['doj'])->addDays(90)->toDateString()
                        : null),
                    'last_working_date' => $validated['lwd'] ?? null,
                    'employment_type' => $validated['type'] ?? 'Permanent',
                    'status' => ($validated['type'] ?? '') === 'Probation' ? 'Probation' : 'Active',
                    'cnic_document_path' => $cnicDocumentMeta['path'],
                    'cnic_document_name' => $cnicDocumentMeta['name'],
                    'basic_salary' => $validated['basic'] ?? null,
                    'house_allowance' => $validated['house'] ?? null,
                    'transport_allowance' => $validated['transport'] ?? null,
                    'tax_deduction' => $validated['tax'] ?? null,
                    'bank_name' => $validated['bank'] ?? null,
                    'bank_account_no' => $validated['acct'] ?? null,
                    'bank_iban' => $validated['iban'] ?? null,
                    'personal_phone' => $validated['phone'] ?? null,
                    'personal_email' => $validated['email'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('reporting_lines')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'manager_user_id' => $managerUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            return $userId;
        });

        return response()->json([
            'ok' => true,
            'user_id' => $userId,
            'temporary_password' => $createdPassword,
        ], 201);
    }

    public function update(Request $request, string $employeeCode): JsonResponse
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:80'],
            'lname' => ['required', 'string', 'max:80'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    DB::table('users')->where('employee_code', $employeeCode)->value('id')
                ),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'dept' => ['required', 'string', 'max:255'],
            'desg' => ['required', 'string', 'max:255'],
            'doj' => ['required', 'date_format:Y-m-d'],
            'dop' => ['nullable', 'date_format:Y-m-d'],
            'lwd' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:30'],
            'manager' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'cnic_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'address' => ['nullable', 'string', 'max:255'],
            'blood' => ['nullable', 'string', 'max:10'],
            'kin' => ['nullable', 'string', 'max:255'],
            'kinRel' => ['nullable', 'string', 'max:255'],
            'kinPhone' => ['nullable', 'string', 'max:40'],
            'basic' => ['nullable', 'integer', 'min:0'],
            'house' => ['nullable', 'integer', 'min:0'],
            'transport' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'bank' => ['nullable', 'string', 'max:255'],
            'acct' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = DB::table('users')->where('employee_code', $employeeCode)->value('id');
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $departmentId = DB::table('departments')->where('name', $validated['dept'])->value('id');
        if (!$departmentId) {
            $departmentId = DB::table('departments')->insertGetId([
                'name' => $validated['dept'],
                'color' => '#2447D0',
                'head_user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $managerUserId = null;
        if (!empty($validated['manager'])) {
            $managerUserId = DB::table('users')->where('name', $validated['manager'])->value('id');
        }

        $name = trim($validated['fname'].' '.$validated['lname']);

        DB::transaction(function () use ($validated, $userId, $name, $departmentId, $managerUserId, $request, $employeeCode) {
            $userUpdate = [
                'name' => $name,
                'email' => $validated['email'],
                'updated_at' => now(),
            ];

            if (!empty($validated['password'])) {
                $userUpdate['password'] = Hash::make($validated['password']);
            }

            DB::table('users')->where('id', $userId)->update($userUpdate);

            $profile = DB::table('employee_profiles')->where('user_id', $userId)->first();
            $cnicDocumentMeta = null;
            if ($request->hasFile('cnic_document')) {
                $cnicDocumentMeta = $this->storeEmployeeDocument($request->file('cnic_document'), $employeeCode, $userId);
            }

            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'department_id' => $departmentId,
                    'manager_user_id' => $managerUserId,
                    'designation' => $validated['desg'],
                    'date_of_joining' => $validated['doj'],
                    'probation_end_date' => $validated['dop'] ?? null,
                    'last_working_date' => $validated['lwd'] ?? null,
                    'employment_type' => $validated['type'] ?? 'Permanent',
                    'status' => $validated['status'] ?? 'Active',
                    'date_of_birth' => $validated['dob'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'cnic' => $validated['cnic'] ?? null,
                    'cnic_document_path' => $cnicDocumentMeta['path'] ?? ($profile?->cnic_document_path),
                    'cnic_document_name' => $cnicDocumentMeta['name'] ?? ($profile?->cnic_document_name),
                    'personal_phone' => $validated['phone'] ?? null,
                    'personal_email' => $validated['email'],
                    'address' => $validated['address'] ?? null,
                    'blood_group' => $validated['blood'] ?? null,
                    'next_of_kin_name' => $validated['kin'] ?? null,
                    'next_of_kin_relationship' => $validated['kinRel'] ?? null,
                    'next_of_kin_phone' => $validated['kinPhone'] ?? null,
                    'basic_salary' => $validated['basic'] ?? null,
                    'house_allowance' => $validated['house'] ?? null,
                    'transport_allowance' => $validated['transport'] ?? null,
                    'tax_deduction' => $validated['tax'] ?? null,
                    'bank_name' => $validated['bank'] ?? null,
                    'bank_account_no' => $validated['acct'] ?? null,
                    'bank_iban' => $validated['iban'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('reporting_lines')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'manager_user_id' => $managerUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        return response()->json(['ok' => true]);
    }

    public function deleteCnicDocument(Request $request, string $employeeCode): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }

        $profile = DB::table('employee_profiles')
            ->join('users', 'users.id', '=', 'employee_profiles.user_id')
            ->where('users.employee_code', $employeeCode)
            ->select([
                'employee_profiles.user_id',
                'employee_profiles.cnic_document_path',
            ])
            ->first();

        if (!$profile) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        if ($profile->cnic_document_path) {
            $absolutePath = public_path($profile->cnic_document_path);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        DB::table('employee_profiles')
            ->where('user_id', $profile->user_id)
            ->update([
                'cnic_document_path' => null,
                'cnic_document_name' => null,
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $employeeCode): JsonResponse
    {
        $userId = DB::table('users')->where('employee_code', $employeeCode)->value('id');
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        if ($request->user()->id === $userId) {
            return response()->json(['ok' => false, 'message' => 'Cannot delete yourself'], 422);
        }

        DB::table('users')->where('id', $userId)->delete();

        return response()->json(['ok' => true]);
    }

    private function employeeQuery(bool $includeConfidential)
    {
        $select = [
            'users.id as user_id',
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as department',
            'employee_profiles.designation',
            'employee_profiles.date_of_joining',
            'employee_profiles.probation_end_date',
            'employee_profiles.last_working_date',
            'employee_profiles.status',
            'employee_profiles.employment_type',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'employee_profiles.date_of_birth',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.cnic_document_path',
            'employee_profiles.cnic_document_name',
            'employee_profiles.address',
            'employee_profiles.blood_group',
            'employee_profiles.next_of_kin_name',
            'employee_profiles.next_of_kin_relationship',
            'employee_profiles.next_of_kin_phone',
            'mgr.name as manager_name',
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
            ->select($select);
    }

    private function formatEmployeeRecord(object $record, bool $includeConfidential): array
    {
        $parts = preg_split('/\s+/', trim((string) $record->name)) ?: [];
        $fname = $parts[0] ?? $record->name;
        $lname = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        $payload = [
            'id' => $record->employee_code,
            'userId' => $record->user_id,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $record->email,
            'role' => $record->role,
            'dept' => $record->department ?? '-',
            'desg' => $record->designation ?? '-',
            'doj' => $record->date_of_joining,
            'dop' => $record->probation_end_date,
            'lwd' => $record->last_working_date,
            'status' => $record->status ?? 'Active',
            'type' => $record->employment_type,
            'phone' => $record->personal_phone,
            'personalEmail' => $record->personal_email,
            'manager' => $record->manager_name ?? '-',
            'dob' => $record->date_of_birth,
            'gender' => $record->gender,
            'cnic' => $record->cnic,
            'cnicDocumentPath' => $record->cnic_document_path,
            'cnicDocumentName' => $record->cnic_document_name,
            'cnicDocumentUrl' => $record->cnic_document_path ? asset($record->cnic_document_path) : null,
            'address' => $record->address,
            'blood' => $record->blood_group,
            'kin' => $record->next_of_kin_name,
            'kinRel' => $record->next_of_kin_relationship,
            'kinPhone' => $record->next_of_kin_phone,
        ];

        if ($includeConfidential) {
            $payload = array_merge($payload, [
                'basic' => $record->basic_salary,
                'house' => $record->house_allowance,
                'transport' => $record->transport_allowance,
                'tax' => $record->tax_deduction,
                'bank' => $record->bank_name,
                'acct' => $record->bank_account_no,
                'iban' => $record->bank_iban,
            ]);
        }

        return $payload;
    }

    private function storeEmployeeDocument($file, string $employeeCode, int $userId): array
    {
        $directory = public_path('uploads/employee-documents');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = sprintf('cnic-%s-%s.%s', $employeeCode ?: $userId, Str::lower(Str::random(8)), $extension);

        $file->move($directory, $filename);

        return [
            'path' => 'uploads/employee-documents/'.$filename,
            'name' => $file->getClientOriginalName(),
        ];
    }
}
