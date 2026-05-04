<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\DeletionRecoveryService;
use App\Mail\NewEmployeeCredentialsMail;

class EmployeesController extends Controller
{
    private const OFFBOARDING_STATUS = 'Offboarding';
    private const EX_EMPLOYEE_STATUSES = ['Inactive', 'Resigned'];

    private function isSuperAdminRole(object $user): bool
    {
        return method_exists($user, 'canonicalRole')
            ? $user->canonicalRole() === 'manager'
            : in_array((string) ($user->role ?? ''), ['manager', 'super_admin', 'super-admin', 'super admin'], true);
    }

    private function isSuperAdminAccountRole(?string $role): bool
    {
        return in_array((string) $role, ['manager', 'super_admin', 'super-admin', 'super admin'], true);
    }

    private function normalizeEmail(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    private function emailExistsAsOfficial(string $email, ?int $ignoreUserId = null): bool
    {
        $normalized = $this->normalizeEmail($email);

        return DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    private function emailExistsAsPersonal(string $email, ?int $ignoreUserId = null): bool
    {
        $normalized = $this->normalizeEmail($email);

        return DB::table('employee_profiles')
            ->whereNotNull('personal_email')
            ->whereRaw('LOWER(personal_email) = ?', [$normalized])
            ->when($ignoreUserId, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }

    private function validateDistinctEmployeeEmails(string $officialEmail, string $personalEmail, ?int $ignoreUserId = null): ?JsonResponse
    {
        $official = $this->normalizeEmail($officialEmail);
        $personal = $this->normalizeEmail($personalEmail);

        if ($official === $personal) {
            return response()->json(['ok' => false, 'message' => 'Official email and personal email must be different.'], 422);
        }

        if ($this->emailExistsAsOfficial($official, $ignoreUserId)) {
            return response()->json(['ok' => false, 'message' => 'Official email is already used as another employee official email.'], 422);
        }

        if ($this->emailExistsAsPersonal($official, $ignoreUserId)) {
            return response()->json(['ok' => false, 'message' => 'Official email is already used as another employee personal email.'], 422);
        }

        if ($this->emailExistsAsOfficial($personal, $ignoreUserId)) {
            return response()->json(['ok' => false, 'message' => 'Personal email is already used as another employee official email.'], 422);
        }

        if ($this->emailExistsAsPersonal($personal, $ignoreUserId)) {
            return response()->json(['ok' => false, 'message' => 'Personal email is already used as another employee personal email.'], 422);
        }

        return null;
    }

    public function show(Request $request, string $employeeCode): JsonResponse
    {
        $user = $request->user();
        $includeConfidential = $user->isSuperAdmin();
        $record = $this->employeeQuery($includeConfidential)
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        if (
            $this->isSuperAdminAccountRole($record->role ?? null)
            && (int) $record->user_id !== (int) $user->id
            && !$this->isSuperAdminRole($user)
        ) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can view Super-Admin accounts.'], 403);
        }

        if (!$this->canViewEmployeeRecord($user, $record)) {
            abort(403);
        }

        $includeSensitiveProfile = in_array($user->role, ['admin', 'manager', 'hr'], true)
            || (int) $record->user_id === (int) $user->id;

        return response()->json([
            'ok' => true,
            'employee' => $this->formatEmployeeRecord($record, $includeConfidential, $includeSensitiveProfile),
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
            'personal_email' => ['required', 'email', 'max:255'],
            'dept' => ['required', 'string', 'max:255'],
            'desg' => ['required', 'string', 'max:255'],
            'doj' => ['required', 'date_format:Y-m-d'],
            'dop' => ['nullable', 'date_format:Y-m-d'],
            'lwd' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'confirmation_date' => ['nullable', 'date_format:Y-m-d'],
            'manager' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(['employee', 'manager', 'admin'])], // default employee
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'cnic_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'dob' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'passport_no' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'blood' => ['nullable', 'string', 'max:10'],
            'kin' => ['nullable', 'string', 'max:255'],
            'kinRel' => ['nullable', 'string', 'max:255'],
            'kinPhone' => ['nullable', 'string', 'max:40'],
            'basic' => ['nullable', 'integer', 'min:0'],
            'house' => ['nullable', 'integer', 'min:0'],
            'transport' => ['nullable', 'integer', 'min:0'],
            'pay_period' => ['nullable', 'string', 'max:50'],
            'salary_start_date' => ['nullable', 'date_format:Y-m-d'],
            'contribution' => ['nullable', 'integer', 'min:0'],
            'other_deductions' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'bank' => ['nullable', 'string', 'max:255'],
            'acct' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ]);

        $requestedRole = $validated['role'] ?? 'employee';
        $role = in_array($requestedRole, ['employee', 'manager', 'admin'], true)
            ? $requestedRole
            : 'employee';

        if ($role === 'manager' && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can create Super-Admin accounts.'], 403);
        }

        if ($emailError = $this->validateDistinctEmployeeEmails($validated['email'], $validated['personal_email'])) {
            return $emailError;
        }

        $existingUserForEmail = DB::table('users')->where('email', $validated['email'])->first();
        if ($existingUserForEmail && $this->isSuperAdminAccountRole($existingUserForEmail->role ?? null) && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can change Super-Admin accounts.'], 403);
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
            $managerUserId = $this->resolveManagerUserId($validated['manager']);
        }

        $employeeCode = $this->nextEmployeeCodeForTeam($validated['dept']);
        $name = trim($validated['fname'].' '.$validated['lname']);

        $createdPassword = null;
        $cnicDocument = $request->file('cnic_document');

        $userId = DB::transaction(function () use ($validated, $role, $employeeCode, $name, $departmentId, $managerUserId, &$createdPassword, $cnicDocument) {
            $existing = DB::table('users')->where('email', $validated['email'])->first();
            if ($existing) {
                $resolvedEmployeeCode = $this->employeeCodeMatchesTeam((string) ($existing->employee_code ?? ''), $validated['dept'])
                    ? (string) $existing->employee_code
                    : $this->nextEmployeeCodeForTeam($validated['dept'], (int) $existing->id);

                // Update existing account to become an employee profile if needed
                DB::table('users')->where('id', $existing->id)->update([
                    'name' => $name,
                    'role' => $existing->role ?: $role,
                    'employee_code' => $resolvedEmployeeCode,
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

            $cnicDocumentMeta = $cnicDocument
                ? $this->storeEmployeeDocument($cnicDocument, $employeeCode, $userId)
                : ['path' => null, 'name' => null];

            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'department_id' => $departmentId,
                    'manager_user_id' => $managerUserId,
                    'shift_id' => $validated['shift_id'] ?? null,
                    'designation' => $validated['desg'],
                    'date_of_joining' => $validated['doj'],
                    'probation_end_date' => $validated['dop'] ?? (($validated['type'] ?? '') === 'Probation'
                        ? now()->parse($validated['doj'])->addDays(90)->toDateString()
                        : null),
                    'last_working_date' => $validated['lwd'] ?? null,
                    'employment_type' => $validated['type'] ?? 'Permanent',
                    'status' => $this->resolveEmploymentStatus(
                        ($validated['type'] ?? '') === 'Probation' ? 'Probation' : 'Active',
                        $validated['lwd'] ?? null,
                        !empty($validated['lwd']),
                    ),
                    'work_location' => $validated['work_location'] ?? null,
                    'confirmation_date' => $validated['confirmation_date'] ?? null,
                    'date_of_birth' => $validated['dob'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'cnic' => $validated['cnic'] ?? null,
                    'cnic_document_path' => $cnicDocumentMeta['path'],
                    'cnic_document_name' => $cnicDocumentMeta['name'],
                    'marital_status' => $validated['marital_status'] ?? null,
                    'passport_no' => $validated['passport_no'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'blood_group' => $validated['blood'] ?? null,
                    'next_of_kin_name' => $validated['kin'] ?? null,
                    'next_of_kin_relationship' => $validated['kinRel'] ?? null,
                    'next_of_kin_phone' => $validated['kinPhone'] ?? null,
                    'basic_salary' => $validated['basic'] ?? null,
                    'house_allowance' => $validated['house'] ?? null,
                    'transport_allowance' => $validated['transport'] ?? null,
                    'pay_period' => $validated['pay_period'] ?? null,
                    'salary_start_date' => $validated['salary_start_date'] ?? null,
                    'contribution_amount' => $validated['contribution'] ?? null,
                    'other_deductions' => $validated['other_deductions'] ?? null,
                    'tax_deduction' => $validated['tax'] ?? null,
                    'bank_name' => $validated['bank'] ?? null,
                    'bank_account_no' => $validated['acct'] ?? null,
                    'bank_iban' => $validated['iban'] ?? null,
                    'personal_phone' => $validated['phone'] ?? null,
                    'personal_email' => $validated['personal_email'],
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

        if ($createdPassword) {
            $this->sendNewEmployeeCredentials($name, $validated['email'], $createdPassword);
        }

        return response()->json([
            'ok' => true,
            'user_id' => $userId,
            'temporary_password' => $createdPassword,
        ], 201);
    }

    private function sendNewEmployeeCredentials(string $name, string $email, string $password): void
    {
        try {
            Mail::to($email)->send(new NewEmployeeCredentialsMail(
                employeeName: $name,
                email: $email,
                password: $password,
                loginUrl: url('/musharp'),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
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
            'personal_email' => ['required', 'email', 'max:255'],
            'dept' => ['required', 'string', 'max:255'],
            'desg' => ['required', 'string', 'max:255'],
            'doj' => ['required', 'date_format:Y-m-d'],
            'dop' => ['nullable', 'date_format:Y-m-d'],
            'lwd' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:30'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'confirmation_date' => ['nullable', 'date_format:Y-m-d'],
            'manager' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(['employee', 'manager', 'admin'])],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'dob' => ['nullable', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'passport_no' => ['nullable', 'string', 'max:50'],
            'cnic_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'address' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'blood' => ['nullable', 'string', 'max:10'],
            'kin' => ['nullable', 'string', 'max:255'],
            'kinRel' => ['nullable', 'string', 'max:255'],
            'kinPhone' => ['nullable', 'string', 'max:40'],
            'basic' => ['nullable', 'integer', 'min:0'],
            'house' => ['nullable', 'integer', 'min:0'],
            'transport' => ['nullable', 'integer', 'min:0'],
            'pay_period' => ['nullable', 'string', 'max:50'],
            'salary_start_date' => ['nullable', 'date_format:Y-m-d'],
            'contribution' => ['nullable', 'integer', 'min:0'],
            'other_deductions' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'bank' => ['nullable', 'string', 'max:255'],
            'acct' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = DB::table('users')->where('employee_code', $employeeCode)->value('id');
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $targetRole = DB::table('users')->where('id', $userId)->value('role');
        if ($this->isSuperAdminAccountRole($targetRole) && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can change Super-Admin accounts.'], 403);
        }

        if (($validated['role'] ?? null) === 'manager' && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can create Super-Admin accounts.'], 403);
        }

        if ($emailError = $this->validateDistinctEmployeeEmails($validated['email'], $validated['personal_email'], (int) $userId)) {
            return $emailError;
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
            $managerUserId = $this->resolveManagerUserId($validated['manager']);
        }

        $currentUser = DB::table('users')->where('id', $userId)->first(['employee_code']);
        $currentDepartmentName = DB::table('departments')
            ->where('id', DB::table('employee_profiles')->where('user_id', $userId)->value('department_id'))
            ->value('name');
        $name = trim($validated['fname'].' '.$validated['lname']);
        $updatedEmployeeCode = $employeeCode;

        if ($currentUser) {
            $shouldRefreshCode = !$this->employeeCodeMatchesTeam((string) $currentUser->employee_code, $validated['dept'])
                || (($currentDepartmentName ?? '') !== $validated['dept']);

            $updatedEmployeeCode = $shouldRefreshCode
                ? $this->nextEmployeeCodeForTeam($validated['dept'], $userId)
                : (string) $currentUser->employee_code;
        }

        DB::transaction(function () use ($validated, $userId, $name, $departmentId, $managerUserId, $request, $employeeCode, $updatedEmployeeCode) {
            $userUpdate = [
                'name' => $name,
                'email' => $validated['email'],
                'employee_code' => $updatedEmployeeCode,
                'updated_at' => now(),
            ];

            if (!empty($validated['password'])) {
                $userUpdate['password'] = Hash::make($validated['password']);
            }

            if (!empty($validated['role'])) {
                $userUpdate['role'] = $validated['role'];
            }

            DB::table('users')->where('id', $userId)->update($userUpdate);

            $profile = DB::table('employee_profiles')->where('user_id', $userId)->first();
            $lastWorkingDate = $validated['lwd'] ?? null;
            $lastWorkingDateChanged = array_key_exists('lwd', $validated)
                && (string) ($profile?->last_working_date ?? '') !== (string) ($lastWorkingDate ?? '');
            $cnicDocumentMeta = null;
            if ($request->hasFile('cnic_document')) {
                $cnicDocumentMeta = $this->storeEmployeeDocument($request->file('cnic_document'), $updatedEmployeeCode, $userId);
            }

            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'department_id' => $departmentId,
                    'manager_user_id' => $managerUserId,
                    'shift_id' => $validated['shift_id'] ?? null,
                    'designation' => $validated['desg'],
                    'date_of_joining' => $validated['doj'],
                    'probation_end_date' => $validated['dop'] ?? null,
                    'last_working_date' => $lastWorkingDate,
                    'employment_type' => $validated['type'] ?? 'Permanent',
                    'status' => $this->resolveEmploymentStatus(
                        $validated['status'] ?? ($profile?->status ?? 'Active'),
                        $lastWorkingDate,
                        $lastWorkingDateChanged,
                    ),
                    'work_location' => $validated['work_location'] ?? null,
                    'confirmation_date' => $validated['confirmation_date'] ?? null,
                    'date_of_birth' => $validated['dob'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'cnic' => $validated['cnic'] ?? null,
                    'passport_no' => $validated['passport_no'] ?? null,
                    'cnic_document_path' => $cnicDocumentMeta['path'] ?? ($profile?->cnic_document_path),
                    'cnic_document_name' => $cnicDocumentMeta['name'] ?? ($profile?->cnic_document_name),
                    'personal_phone' => $validated['phone'] ?? null,
                    'personal_email' => $validated['personal_email'],
                    'address' => $validated['address'] ?? null,
                    'marital_status' => $validated['marital_status'] ?? null,
                    'blood_group' => $validated['blood'] ?? null,
                    'next_of_kin_name' => $validated['kin'] ?? null,
                    'next_of_kin_relationship' => $validated['kinRel'] ?? null,
                    'next_of_kin_phone' => $validated['kinPhone'] ?? null,
                    'basic_salary' => $validated['basic'] ?? null,
                    'house_allowance' => $validated['house'] ?? null,
                    'transport_allowance' => $validated['transport'] ?? null,
                    'pay_period' => $validated['pay_period'] ?? null,
                    'salary_start_date' => $validated['salary_start_date'] ?? null,
                    'contribution_amount' => $validated['contribution'] ?? null,
                    'other_deductions' => $validated['other_deductions'] ?? null,
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
        if (!$request->user()->isSuperAdmin()) {
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

        $targetRole = DB::table('users')->where('employee_code', $employeeCode)->value('role');
        if ($this->isSuperAdminAccountRole($targetRole) && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can change Super-Admin accounts.'], 403);
        }

        if ($profile->cnic_document_path) {
            $this->deleteStoredFile((string) $profile->cnic_document_path);
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

    public function downloadCnicDocument(Request $request, string $employeeCode)
    {
        $user = $request->user();
        $includeConfidential = $user->isSuperAdmin();
        $record = $this->employeeQuery($includeConfidential)
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            abort(404);
        }

        if (
            $this->isSuperAdminAccountRole($record->role ?? null)
            && (int) $record->user_id !== (int) $user->id
            && !$this->isSuperAdminRole($user)
        ) {
            abort(403);
        }

        if (!$this->canViewEmployeeRecord($user, $record)) {
            abort(403);
        }

        if (!$record->cnic_document_path) {
            abort(404);
        }

        return $this->downloadPrivateFile((string) $record->cnic_document_path, (string) ($record->cnic_document_name ?: 'employee-document'));
    }

    public function downloadProfilePhoto(Request $request, string $employeeCode)
    {
        $user = $request->user();
        $record = $this->employeeQuery(false)
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            abort(404);
        }

        if (
            $this->isSuperAdminAccountRole($record->role ?? null)
            && (int) $record->user_id !== (int) $user->id
            && !$this->isSuperAdminRole($user)
        ) {
            abort(403);
        }

        if (!$this->canViewEmployeeRecord($user, $record)) {
            abort(403);
        }

        if (!$record->profile_photo_path) {
            abort(404);
        }

        return $this->downloadPrivateFile((string) $record->profile_photo_path, (string) ($record->profile_photo_name ?: 'profile-photo'));
    }

    public function storeOffboardingDocument(Request $request, string $employeeCode): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $record = $this->employeeForManagedOffboarding($request, $employeeCode);
        if ($record instanceof JsonResponse) {
            return $record;
        }

        $file = $request->file('document');
        $meta = $this->storeOffboardingFile($file, $employeeCode, (int) $record->user_id);

        $documentId = DB::table('employee_offboarding_documents')->insertGetId([
            'user_id' => $record->user_id,
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'] ?? null,
            'file_path' => $meta['path'],
            'file_name' => $meta['name'],
            'mime_type' => $meta['mime'],
            'file_size' => $meta['size'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'document' => $this->formatOffboardingDocument(
                DB::table('employee_offboarding_documents')->where('id', $documentId)->first(),
                $employeeCode,
            ),
        ], 201);
    }

    public function updateOffboardingDocument(Request $request, string $employeeCode, int $documentId): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $record = $this->employeeForManagedOffboarding($request, $employeeCode);
        if ($record instanceof JsonResponse) {
            return $record;
        }

        $document = $this->offboardingDocumentForEmployee((int) $record->user_id, $documentId);
        if (!$document) {
            return response()->json(['ok' => false, 'message' => 'Document not found.'], 404);
        }

        $update = [
            'title' => $validated['title'] ?? null,
            'updated_at' => now(),
        ];

        if ($request->hasFile('document')) {
            $this->deleteStoredFile((string) $document->file_path);
            $meta = $this->storeOffboardingFile($request->file('document'), $employeeCode, (int) $record->user_id);
            $update = array_merge($update, [
                'file_path' => $meta['path'],
                'file_name' => $meta['name'],
                'mime_type' => $meta['mime'],
                'file_size' => $meta['size'],
                'uploaded_by' => $request->user()->id,
            ]);
        }

        DB::table('employee_offboarding_documents')->where('id', $documentId)->update($update);

        return response()->json([
            'ok' => true,
            'document' => $this->formatOffboardingDocument(
                DB::table('employee_offboarding_documents')->where('id', $documentId)->first(),
                $employeeCode,
            ),
        ]);
    }

    public function deleteOffboardingDocument(Request $request, string $employeeCode, int $documentId): JsonResponse
    {
        $record = $this->employeeForManagedOffboarding($request, $employeeCode);
        if ($record instanceof JsonResponse) {
            return $record;
        }

        $document = $this->offboardingDocumentForEmployee((int) $record->user_id, $documentId);
        if (!$document) {
            return response()->json(['ok' => false, 'message' => 'Document not found.'], 404);
        }

        $this->deleteStoredFile((string) $document->file_path);
        DB::table('employee_offboarding_documents')->where('id', $documentId)->delete();

        return response()->json(['ok' => true]);
    }

    public function downloadOffboardingDocument(Request $request, string $employeeCode, int $documentId)
    {
        $user = $request->user();
        $record = $this->employeeQuery($user->isSuperAdmin())
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            abort(404);
        }

        if (!$this->canViewEmployeeRecord($user, $record)) {
            abort(403);
        }

        $document = $this->offboardingDocumentForEmployee((int) $record->user_id, $documentId);
        abort_unless($document, 404);

        return $this->downloadPrivateFile((string) $document->file_path, (string) $document->file_name);
    }

    public function completeOffboarding(Request $request, string $employeeCode): JsonResponse
    {
        $record = $this->employeeForManagedOffboarding($request, $employeeCode);
        if ($record instanceof JsonResponse) {
            return $record;
        }

        if (($record->status ?? null) !== self::OFFBOARDING_STATUS) {
            return response()->json([
                'ok' => false,
                'message' => 'Only employees in offboarding can be completed.',
            ], 422);
        }

        $hasDocuments = DB::table('employee_offboarding_documents')
            ->where('user_id', $record->user_id)
            ->exists();

        if (!$hasDocuments) {
            return response()->json([
                'ok' => false,
                'message' => 'Upload at least one offboarding document before completing offboarding.',
            ], 422);
        }

        DB::table('employee_profiles')
            ->where('user_id', $record->user_id)
            ->update([
                'status' => 'Inactive',
                'last_working_date' => DB::raw("COALESCE(last_working_date, '".now()->toDateString()."')"),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Offboarding completed. Employee moved to ex-employee records.',
        ]);
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

        $targetRole = DB::table('users')->where('id', $userId)->value('role');
        if ($this->isSuperAdminAccountRole($targetRole) && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can delete Super-Admin accounts.'], 403);
        }

        $employeeUser = DB::table('users')->where('id', $userId)->first();
        $employeeProfile = DB::table('employee_profiles')->where('user_id', $userId)->first();
        if ($employeeUser && $employeeProfile) {
            app(DeletionRecoveryService::class)->record('employee', (string) ($employeeUser->name ?? $employeeCode), [
                'user' => (array) $employeeUser,
                'profile' => (array) $employeeProfile,
            ], (int) $request->user()->id);
        }

        DB::table('employee_profiles')
            ->where('user_id', $userId)
            ->update([
                'status' => 'Inactive',
                'last_working_date' => DB::raw("COALESCE(last_working_date, '".now()->toDateString()."')"),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Employee moved to ex-employee records.',
        ]);
    }

    private function resolveEmploymentStatus(?string $status, ?string $lastWorkingDate, bool $lastWorkingDateChanged = false): string
    {
        $normalizedStatus = trim((string) ($status ?? '')) ?: 'Active';
        $today = now()->toDateString();

        if ($lastWorkingDateChanged) {
            return self::OFFBOARDING_STATUS;
        }

        if (in_array($normalizedStatus, self::EX_EMPLOYEE_STATUSES, true)) {
            return $normalizedStatus;
        }

        if ($lastWorkingDate) {
            return $lastWorkingDate < $today
                ? 'Inactive'
                : self::OFFBOARDING_STATUS;
        }

        return $normalizedStatus;
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
            'employee_profiles.work_location',
            'employee_profiles.confirmation_date',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'employee_profiles.date_of_birth',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.passport_no',
            'employee_profiles.cnic_document_path',
            'employee_profiles.cnic_document_name',
            'employee_profiles.profile_photo_path',
            'employee_profiles.profile_photo_name',
            'employee_profiles.address',
            'employee_profiles.marital_status',
            'employee_profiles.blood_group',
            'employee_profiles.next_of_kin_name',
            'employee_profiles.next_of_kin_relationship',
            'employee_profiles.next_of_kin_phone',
            'employee_profiles.department_id',
            'employee_profiles.manager_user_id',
            'shifts.id as shift_id',
            'shifts.code as shift_code',
            'shifts.name as shift_name',
            'shifts.start_time',
            'shifts.end_time',
            'shifts.grace_minutes',
            'shifts.working_days',
            'mgr.name as manager_name',
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

        return DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->select($select);
    }

    private function formatEmployeeRecord(object $record, bool $includeConfidential, bool $includeSensitiveProfile = false): array
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
            'workLocation' => $record->work_location,
            'confirmationDate' => $record->confirmation_date,
            'phone' => $includeSensitiveProfile ? $record->personal_phone : null,
            'personalEmail' => $includeSensitiveProfile ? $record->personal_email : null,
            'manager' => $record->manager_name ?? '-',
            'dob' => $includeSensitiveProfile ? $record->date_of_birth : null,
            'gender' => $includeSensitiveProfile ? $record->gender : null,
            'cnic' => $includeSensitiveProfile ? $record->cnic : null,
            'passportNo' => $includeSensitiveProfile ? $record->passport_no : null,
            'cnicDocumentPath' => $includeSensitiveProfile ? $record->cnic_document_path : null,
            'cnicDocumentName' => $includeSensitiveProfile ? $record->cnic_document_name : null,
            'cnicDocumentUrl' => ($includeSensitiveProfile && $record->cnic_document_path) ? url('/api/employees/'.$record->employee_code.'/cnic-document') : null,
            'profilePhotoPath' => $record->profile_photo_path,
            'profilePhotoName' => $record->profile_photo_name,
            'profilePhotoUrl' => $record->profile_photo_path ? url('/api/employees/'.$record->employee_code.'/profile-photo') : null,
            'offboardingDocuments' => $includeSensitiveProfile
                ? $this->offboardingDocumentsForEmployee((int) $record->user_id, (string) $record->employee_code)
                : [],
            'address' => $includeSensitiveProfile ? $record->address : null,
            'maritalStatus' => $includeSensitiveProfile ? $record->marital_status : null,
            'blood' => $includeSensitiveProfile ? $record->blood_group : null,
            'kin' => $includeSensitiveProfile ? $record->next_of_kin_name : null,
            'kinRel' => $includeSensitiveProfile ? $record->next_of_kin_relationship : null,
            'kinPhone' => $includeSensitiveProfile ? $record->next_of_kin_phone : null,
            'shiftId' => $record->shift_id,
            'shiftCode' => $record->shift_code,
            'shiftName' => $record->shift_name,
            'shiftStart' => $record->start_time ? substr((string) $record->start_time, 0, 5) : null,
            'shiftEnd' => $record->end_time ? substr((string) $record->end_time, 0, 5) : null,
            'shiftGrace' => $record->grace_minutes !== null ? (int) $record->grace_minutes : null,
            'shiftWorkingDays' => $record->working_days,
        ];

        if ($includeConfidential) {
            $payload = array_merge($payload, [
                'basic' => $record->basic_salary,
                'house' => $record->house_allowance,
                'transport' => $record->transport_allowance,
                'payPeriod' => $record->pay_period,
                'salaryStartDate' => $record->salary_start_date,
                'contribution' => $record->contribution_amount,
                'otherDeductions' => $record->other_deductions,
                'tax' => $record->tax_deduction,
                'bank' => $record->bank_name,
                'acct' => $record->bank_account_no,
                'iban' => $record->bank_iban,
            ]);
        }

        if ($includeConfidential) {
            $payload['customFields'] = DB::table('employee_custom_field_values')
                ->join('employee_custom_fields', 'employee_custom_fields.id', '=', 'employee_custom_field_values.field_id')
                ->where('employee_custom_field_values.user_id', $record->user_id)
                ->orderBy('employee_custom_fields.label')
                ->get([
                    'employee_custom_fields.label',
                    'employee_custom_field_values.value',
                ])
                ->map(fn ($field) => [
                    'label' => $field->label,
                    'value' => $field->value,
                ])
                ->values()
                ->all();
        }

        return $payload;
    }

    private function storeEmployeeDocument($file, string $employeeCode, int $userId): array
    {
        if (!$file) {
            return ['path' => null, 'name' => null];
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = sprintf('cnic-%s-%s.%s', $employeeCode ?: $userId, Str::lower(Str::random(8)), $extension);
        $path = 'employee-documents/'.$filename;

        Storage::putFileAs('employee-documents', $file, $filename);

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function storeOffboardingFile($file, string $employeeCode, int $userId): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = sprintf('offboarding-%s-%s.%s', $employeeCode ?: $userId, Str::lower(Str::random(10)), $extension);
        $path = 'employee-offboarding-documents/'.$filename;

        Storage::putFileAs('employee-offboarding-documents', $file, $filename);

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    private function employeeForManagedOffboarding(Request $request, string $employeeCode)
    {
        $record = $this->employeeQuery($request->user()->isSuperAdmin())
            ->where('users.employee_code', $employeeCode)
            ->first();

        if (!$record) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        if ($this->isSuperAdminAccountRole($record->role ?? null) && !$this->isSuperAdminRole($request->user())) {
            return response()->json(['ok' => false, 'message' => 'Only a Super-Admin can change Super-Admin accounts.'], 403);
        }

        return $record;
    }

    private function offboardingDocumentForEmployee(int $userId, int $documentId): ?object
    {
        return DB::table('employee_offboarding_documents')
            ->where('id', $documentId)
            ->where('user_id', $userId)
            ->first();
    }

    private function offboardingDocumentsForEmployee(int $userId, string $employeeCode): array
    {
        return DB::table('employee_offboarding_documents')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($document) => $this->formatOffboardingDocument($document, $employeeCode))
            ->values()
            ->all();
    }

    private function formatOffboardingDocument(?object $document, string $employeeCode): ?array
    {
        if (!$document) {
            return null;
        }

        return [
            'id' => $document->id,
            'title' => $document->title,
            'fileName' => $document->file_name,
            'mimeType' => $document->mime_type,
            'fileSize' => $document->file_size !== null ? (int) $document->file_size : null,
            'uploadedAt' => $document->created_at,
            'updatedAt' => $document->updated_at,
            'url' => url('/api/employees/'.$employeeCode.'/offboarding-documents/'.$document->id),
        ];
    }

    private function canViewEmployeeRecord(object $viewer, object $record): bool
    {
        if (
            $this->isSuperAdminAccountRole($record->role ?? null)
            && (int) $record->user_id !== (int) $viewer->id
            && !$this->isSuperAdminRole($viewer)
        ) {
            return false;
        }

        if (in_array($viewer->role, ['admin', 'manager', 'hr'], true)) {
            return true;
        }

        if ((int) $record->user_id === (int) $viewer->id) {
            return true;
        }

        if (!empty($record->manager_user_id) && (int) $record->manager_user_id === (int) $viewer->id) {
            return true;
        }

        $viewerDepartmentId = DB::table('employee_profiles')
            ->where('user_id', $viewer->id)
            ->value('department_id');

        return $viewerDepartmentId !== null
            && (int) $viewerDepartmentId === (int) ($record->department_id ?? 0);
    }

    private function downloadPrivateFile(string $relativePath, string $downloadName)
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $relativePath), '/');
        abort_if(
            str_contains($normalizedPath, '../')
            || str_starts_with($normalizedPath, '..')
            || preg_match('#(^|/)\.\.(/|$)#', $normalizedPath) === 1,
            400,
            'Invalid file path.'
        );

        if (Storage::exists($normalizedPath)) {
            return response()->download(Storage::path($normalizedPath), $downloadName);
        }

        $legacyPublicPath = public_path($normalizedPath);
        abort_unless(is_file($legacyPublicPath), 404);

        return response()->download($legacyPublicPath, $downloadName);
    }

    private function deleteStoredFile(string $relativePath): void
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (Storage::exists($normalizedPath)) {
            Storage::delete($normalizedPath);
            return;
        }

        $legacyPublicPath = public_path($normalizedPath);
        if (is_file($legacyPublicPath)) {
            @unlink($legacyPublicPath);
        }
    }

    private function nextEmployeeCodeForTeam(string $teamName, ?int $ignoreUserId = null): string
    {
        $prefix = $this->teamPrefix($teamName);

        $query = DB::table('users')
            ->where('employee_code', 'like', $prefix.'-emp%');

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        $existing = $query->pluck('employee_code');

        $max = 0;
        foreach ($existing as $code) {
            if (preg_match('/^'.preg_quote($prefix, '/').'\-emp(\d+)$/i', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix.'-emp'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function employeeCodeMatchesTeam(string $employeeCode, string $teamName): bool
    {
        return str_starts_with(Str::lower($employeeCode), Str::lower($this->teamPrefix($teamName).'-emp'));
    }

    private function teamPrefix(string $teamName): string
    {
        $normalized = Str::of($teamName)->trim()->lower()->replaceMatches('/[^a-z0-9\s]+/', '')->value();

        $mapped = [
            'development' => 'Dev',
            'developer' => 'Dev',
            'engineering' => 'Eng',
            'engineer' => 'Eng',
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
            $initials = strtoupper(implode('', array_map(static fn (string $word): string => substr($word, 0, 1), array_slice($words, 0, 3))));
            return $initials ?: 'Tem';
        }

        $base = ucfirst(substr($normalized ?: 'team', 0, 3));
        return strlen($base) >= 2 ? $base : 'Tem';
    }

    private function resolveManagerUserId(string $managerIdentifier): ?int
    {
        $normalized = trim($managerIdentifier);
        if ($normalized === '') {
            return null;
        }

        $byEmployeeCode = DB::table('users')
            ->where('employee_code', $normalized)
            ->value('id');

        if ($byEmployeeCode) {
            return (int) $byEmployeeCode;
        }

        $byExactName = DB::table('users')
            ->where('name', $normalized)
            ->value('id');

        return $byExactName ? (int) $byExactName : null;
    }
}
