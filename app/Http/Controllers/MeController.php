<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MeController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = DB::table('employee_notifications')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($notification) => [
                'id' => (int) $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'referenceType' => $notification->reference_type,
                'referenceCode' => $notification->reference_code,
                'meta' => $notification->meta ? json_decode((string) $notification->meta, true) : null,
                'isRead' => (bool) $notification->is_read,
                'readAt' => $notification->read_at,
                'createdAt' => $notification->created_at,
            ])
            ->values();

        $unreadCount = DB::table('employee_notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'ok' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::table('employee_notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeConfidential = true;

        $select = [
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as dept',
            'employee_profiles.designation as desg',
            'employee_profiles.date_of_joining as doj',
            'employee_profiles.probation_end_date as dop',
            'employee_profiles.last_working_date as lwd',
            'employee_profiles.manager_user_id',
            'employee_profiles.shift_id',
            'mgr.name as manager',
            'employee_profiles.employment_type as type',
            'employee_profiles.status',
            'employee_profiles.work_location',
            'employee_profiles.confirmation_date',
            'employee_profiles.profile_photo_path',
            'employee_profiles.profile_photo_name',
            // personal
            'employee_profiles.date_of_birth as dob',
            'employee_profiles.gender',
            'employee_profiles.cnic',
            'employee_profiles.passport_no',
            'employee_profiles.personal_phone as phone',
            'employee_profiles.personal_email',
            'employee_profiles.address',
            'employee_profiles.marital_status',
            'employee_profiles.blood_group as blood',
            // next of kin
            'employee_profiles.next_of_kin_name as kin',
            'employee_profiles.next_of_kin_relationship as kinRel',
            'employee_profiles.next_of_kin_phone as kinPhone',
            'shifts.code as shiftCode',
            'shifts.name as shiftName',
            'shifts.start_time as shiftStart',
            'shifts.end_time as shiftEnd',
            'shifts.grace_minutes as shiftGrace',
            'shifts.break_minutes as shiftBreak',
            'shifts.working_days as shiftWorkingDays',
        ];

        if ($canSeeConfidential) {
            $select = array_merge($select, [
                'employee_profiles.basic_salary as basic',
                'employee_profiles.house_allowance as house',
                'employee_profiles.transport_allowance as transport',
                'employee_profiles.pay_period',
                'employee_profiles.salary_start_date',
                'employee_profiles.contribution_amount as contribution',
                'employee_profiles.other_deductions',
                'employee_profiles.tax_deduction as tax',
                'employee_profiles.bank_name as bank',
                'employee_profiles.bank_account_no as acct',
                'employee_profiles.bank_iban as iban',
            ]);
        }

        $row = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->where('users.id', $user->id)
            ->select($select)
            ->first();

        if ($row) {
            $attendancePolicies = DB::table('module_policies')
                ->where('module', 'attendance')
                ->whereIn('key', ['shift_start', 'shift_end', 'grace_minutes'])
                ->pluck('value', 'key');
            $defaultShiftStart = preg_match('/^\d{2}:\d{2}$/', (string) ($attendancePolicies['shift_start'] ?? ''))
                ? (string) $attendancePolicies['shift_start']
                : '11:00';
            $defaultShiftEnd = preg_match('/^\d{2}:\d{2}$/', (string) ($attendancePolicies['shift_end'] ?? ''))
                ? (string) $attendancePolicies['shift_end']
                : '20:00';

            $row->shiftStart = $row->shiftStart ? substr((string) $row->shiftStart, 0, 5) : $defaultShiftStart;
            $row->shiftEnd = $row->shiftEnd ? substr((string) $row->shiftEnd, 0, 5) : $defaultShiftEnd;
            $row->shiftGrace = $row->shiftGrace !== null ? (int) $row->shiftGrace : max(0, (int) ($attendancePolicies['grace_minutes'] ?? 10));
            $row->shiftBreak = $row->shiftBreak !== null ? (int) $row->shiftBreak : 60;
            $row->profilePhotoUrl = $row->profile_photo_path
                ? url('/api/employees/'.$row->employee_code.'/profile-photo')
                : null;
        }

        return response()->json(['ok' => true, 'profile' => $row]);
    }

    public function updateAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['nullable', 'current_password', 'required_with:password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (empty($validated['password']) && !$request->hasFile('profile_photo')) {
            return response()->json([
                'ok' => false,
                'message' => 'Add a new password or choose a profile picture to update your account.',
            ], 422);
        }

        DB::transaction(function () use ($request, $user, $validated) {
            if (!empty($validated['password'])) {
                DB::table('users')->where('id', $user->id)->update([
                    'password' => Hash::make($validated['password']),
                    'updated_at' => now(),
                ]);
            }

            if ($request->hasFile('profile_photo')) {
                $profile = DB::table('employee_profiles')
                    ->where('user_id', $user->id)
                    ->first(['profile_photo_path', 'profile_photo_name']);

                $photoMeta = $this->storeProfilePhoto($request->file('profile_photo'), $user->employee_code ?? (string) $user->id);

                if ($profile?->profile_photo_path) {
                    $this->deleteStoredProfilePhoto((string) $profile->profile_photo_path);
                }

                DB::table('employee_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'profile_photo_path' => $photoMeta['path'],
                        'profile_photo_name' => $photoMeta['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Account updated successfully.',
        ]);
    }

    private function storeProfilePhoto($file, string $employeeCode): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = sprintf('profile-%s-%s.%s', $employeeCode, Str::lower(Str::random(8)), $extension);

        Storage::putFileAs('profile-photos', $file, $filename);

        return [
            'path' => 'profile-photos/'.$filename,
            'name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteStoredProfilePhoto(string $relativePath): void
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
}
