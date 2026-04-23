<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    private function summarizeAttendancePunches($punches): array
    {
        $clockIn = $punches->firstWhere('type', 'clock_in');

        if (!$clockIn) {
            return [
                'status' => 'Absent',
                'late' => false,
                'worked_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $clockInAt = now()->parse($clockIn->punched_at);
        $lastClockOutAt = null;
        $activeClockInAt = null;
        $openBreakAt = null;
        $workedMinutes = 0;

        foreach ($punches as $punch) {
            $punchedAt = now()->parse($punch->punched_at);

            if ($punch->type === 'clock_in') {
                if (!$activeClockInAt) {
                    $activeClockInAt = $punchedAt;
                }
                $openBreakAt = null;
                continue;
            }

            if ($punch->type === 'break_out' && $activeClockInAt && !$openBreakAt) {
                $openBreakAt = $punchedAt;
                continue;
            }

            if ($punch->type === 'break_in' && $activeClockInAt && $openBreakAt) {
                $workedMinutes -= $openBreakAt->diffInMinutes($punchedAt);
                $openBreakAt = null;
                continue;
            }

            if ($punch->type !== 'clock_out' || !$activeClockInAt) {
                continue;
            }

            if ($openBreakAt) {
                $workedMinutes -= $openBreakAt->diffInMinutes($punchedAt);
                $openBreakAt = null;
            }

            if ($punchedAt->greaterThan($activeClockInAt)) {
                $workedMinutes += $activeClockInAt->diffInMinutes($punchedAt);
            }

            $activeClockInAt = null;
            $lastClockOutAt = $punchedAt;
        }

        $workedMinutes = max(0, $workedMinutes);
        $late = $clockInAt->copy()->greaterThan($clockInAt->copy()->setTime(11, 10));
        $overtimeMinutes = 0;

        if ($lastClockOutAt) {
            $shiftEnd = $lastClockOutAt->copy()->setTime(20, 0);
            if ($lastClockOutAt->greaterThan($shiftEnd)) {
                $overtimeMinutes = $shiftEnd->diffInMinutes($lastClockOutAt);
            }
        }

        return [
            'status' => 'Present',
            'late' => $late,
            'worked_minutes' => $workedMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    private function normalizeDurationType(?string $durationType): string
    {
        return in_array($durationType, ['full_day', 'half_day'], true) ? $durationType : 'full_day';
    }

    private function normalizeHalfDaySlot(?string $slot): ?string
    {
        return in_array($slot, ['first_half', 'second_half'], true) ? $slot : null;
    }

    private function getDateRange(string $fromDate, string $toDate): array
    {
        $dates = [];
        $cursor = now()->parse($fromDate)->startOfDay();
        $end = now()->parse($toDate)->startOfDay();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    private function normalizeDailyBreakdownInput(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private function buildLeavePlan(
        string $fromDate,
        string $toDate,
        string $durationType,
        ?string $halfDaySlot,
        array $dailyBreakdown = [],
    ): array {
        $dates = $this->getDateRange($fromDate, $toDate);

        if ($dailyBreakdown !== []) {
            $normalized = [];
            foreach ($dailyBreakdown as $row) {
                if (!is_array($row) || empty($row['date']) || !in_array($row['date'], $dates, true)) {
                    continue;
                }

                $rowDurationType = $this->normalizeDurationType($row['duration_type'] ?? 'full_day');
                $rowHalfDaySlot = $this->normalizeHalfDaySlot($row['half_day_slot'] ?? null);

                if ($rowDurationType === 'half_day' && !$rowHalfDaySlot) {
                    continue;
                }

                $normalized[$row['date']] = [
                    'date' => $row['date'],
                    'duration_type' => $rowDurationType,
                    'half_day_slot' => $rowDurationType === 'half_day' ? $rowHalfDaySlot : null,
                    'days' => $rowDurationType === 'half_day' ? 0.5 : 1.0,
                ];
            }

            if (count($normalized) !== count($dates)) {
                throw ValidationException::withMessages([
                    'daily_breakdown' => 'Choose leave duration for each selected date.',
                ]);
            }

            return array_values(array_map(
                fn (string $date) => $normalized[$date],
                $dates
            ));
        }

        if ($durationType === 'half_day') {
            return [[
                'date' => $fromDate,
                'duration_type' => 'half_day',
                'half_day_slot' => $halfDaySlot,
                'days' => 0.5,
            ]];
        }

        return array_map(fn (string $date) => [
            'date' => $date,
            'duration_type' => 'full_day',
            'half_day_slot' => null,
            'days' => 1.0,
        ], $dates);
    }

    private function expandStoredLeavePlan(object $leaveRequest): array
    {
        return $this->buildLeavePlan(
            $leaveRequest->from_date,
            $leaveRequest->to_date,
            $this->normalizeDurationType($leaveRequest->duration_type ?? 'full_day'),
            $this->normalizeHalfDaySlot($leaveRequest->half_day_slot ?? null),
            $this->normalizeDailyBreakdownInput($leaveRequest->daily_breakdown ?? [])
        );
    }

    private function calculateRequestedLeaveDays(string $fromDate, string $toDate, string $durationType): float
    {
        if ($durationType === 'half_day') {
            return 0.5;
        }

        $from = now()->parse($fromDate)->startOfDay();
        $to = now()->parse($toDate)->startOfDay();

        return (float) max(1, $from->diffInDays($to) + 1);
    }

    private function calculateRequestedLeaveDaysFromPlan(array $plan): float
    {
        return (float) array_sum(array_map(
            fn (array $row) => (float) ($row['days'] ?? 0),
            $plan
        ));
    }

    private function leaveRequestsOverlap(
        int $userId,
        string $fromDate,
        string $toDate,
        string $durationType,
        ?string $halfDaySlot,
        array $dailyBreakdown = [],
        ?int $ignoreRequestId = null,
    ): bool {
        $requestedPlan = $this->buildLeavePlan($fromDate, $toDate, $durationType, $halfDaySlot, $dailyBreakdown);
        $requestedMap = collect($requestedPlan)->keyBy('date');

        $existingRequests = DB::table('leave_requests')
            ->where('user_id', $userId)
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where('from_date', '<=', $toDate)
            ->where('to_date', '>=', $fromDate)
            ->when($ignoreRequestId, fn ($query) => $query->where('id', '!=', $ignoreRequestId))
            ->get(['id', 'from_date', 'to_date', 'duration_type', 'half_day_slot', 'daily_breakdown']);

        foreach ($existingRequests as $existing) {
            $existingPlan = $this->expandStoredLeavePlan($existing);

            foreach ($existingPlan as $existingDay) {
                $requestedDay = $requestedMap->get($existingDay['date']);
                if (!$requestedDay) {
                    continue;
                }

                if (
                    $requestedDay['duration_type'] === 'half_day'
                    && $existingDay['duration_type'] === 'half_day'
                    && $requestedDay['half_day_slot']
                    && $existingDay['half_day_slot']
                    && $requestedDay['half_day_slot'] !== $existingDay['half_day_slot']
                ) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    private function formatLeaveDurationLabel(float $days, string $durationType, ?string $halfDaySlot): string
    {
        if ($durationType === 'half_day') {
            return $halfDaySlot === 'second_half' ? 'Second Half (0.5 day)' : 'First Half (0.5 day)';
        }

        return rtrim(rtrim(number_format($days, 2, '.', ''), '0'), '.').' day(s)';
    }

    private function createEmployeeNotification(
        int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $referenceType = null,
        ?string $referenceCode = null,
        ?array $meta = null,
    ): void {
        DB::table('employee_notifications')->insert([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'reference_type' => $referenceType,
            'reference_code' => $referenceCode,
            'meta' => $meta ? json_encode($meta) : null,
            'is_read' => false,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function syncAttendanceWithLeaveStatus(
        int $userId,
        string $fromDate,
        string $toDate,
        bool $approved,
        string $durationType = 'full_day',
        ?string $halfDaySlot = null,
        ?array $dailyBreakdown = null,
    ): void
    {
        $plan = $dailyBreakdown !== null
            ? $this->buildLeavePlan($fromDate, $toDate, $durationType, $halfDaySlot, $dailyBreakdown)
            : $this->buildLeavePlan($fromDate, $toDate, $durationType, $halfDaySlot);
        $planMap = collect($plan)->keyBy('date');

        foreach ($this->getDateRange($fromDate, $toDate) as $date) {
            if ($approved) {
                $entry = $planMap->get($date);
                if (!$entry) {
                    continue;
                }

                DB::table('attendance_days')->updateOrInsert(
                    ['user_id' => $userId, 'date' => $date],
                    [
                        'status' => $entry['duration_type'] === 'half_day'
                            ? ($entry['half_day_slot'] === 'second_half' ? 'Half Leave (Second Half)' : 'Half Leave (First Half)')
                            : 'Leave',
                        'late' => false,
                        'overtime_minutes' => 0,
                        'worked_minutes' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } else {
                $punches = DB::table('attendance_punches')
                    ->where('user_id', $userId)
                    ->where('date', $date)
                    ->orderBy('punched_at')
                    ->get();

                if ($punches->isEmpty()) {
                    DB::table('attendance_days')
                        ->where('user_id', $userId)
                        ->where('date', $date)
                        ->delete();
                } else {
                    $summary = $this->summarizeAttendancePunches($punches);

                    DB::table('attendance_days')->updateOrInsert(
                        ['user_id' => $userId, 'date' => $date],
                        [
                            'status' => $summary['status'],
                            'late' => $summary['late'],
                            'overtime_minutes' => $summary['overtime_minutes'],
                            'worked_minutes' => $summary['worked_minutes'],
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    private function ensureAdmin(Request $request): void
    {
        if ($request->user()->role !== 'admin') {
            abort(403);
        }
    }

    private function makeLeaveTypeCode(string $name): string
    {
        $base = Str::of($name)->lower()->slug('_')->value();
        $base = trim($base, '_');

        return $base !== '' ? $base : 'leave_type';
    }

    private function calculatePolicyAllocation(int $userId, int $year, float $quota, bool $proRata): float
    {
        $quota = max(0, $quota);
        if (!$proRata || $quota <= 0) {
            return round($quota, 2);
        }

        $dateOfJoining = DB::table('employee_profiles')
            ->where('user_id', $userId)
            ->value('date_of_joining');

        if (!$dateOfJoining) {
            return round($quota, 2);
        }

        $joinDate = now()->parse($dateOfJoining)->startOfDay();
        if ((int) $joinDate->format('Y') > $year) {
            return 0.0;
        }

        if ((int) $joinDate->format('Y') < $year) {
            return round($quota, 2);
        }

        $yearStart = now()->setDate($year, 1, 1)->startOfDay();
        $yearEnd = now()->setDate($year, 12, 31)->startOfDay();
        $effectiveStart = $joinDate->greaterThan($yearStart) ? $joinDate : $yearStart;

        if ($effectiveStart->greaterThan($yearEnd)) {
            return 0.0;
        }

        $eligibleDays = $effectiveStart->diffInDays($yearEnd) + 1;
        $daysInYear = $yearStart->diffInDays($yearEnd) + 1;

        return round(($quota * $eligibleDays) / max(1, $daysInYear), 2);
    }

    private function getOrCreateLeaveBalance(int $userId, int $year, int $leaveTypeId): object
    {
        $existing = DB::table('leave_balances')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $policy = DB::table('leave_policies')
            ->where('year', $year)
            ->where('leave_type_id', $leaveTypeId)
            ->first(['quota_days', 'pro_rata']);

        $allocated = $this->calculatePolicyAllocation(
            $userId,
            $year,
            (float) ($policy->quota_days ?? 0),
            (bool) ($policy->pro_rata ?? false)
        );

        $used = (float) (DB::table('leave_requests')
            ->where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'Approved')
            ->whereYear('from_date', $year)
            ->sum('days') ?? 0);

        $remaining = max(0, $allocated - $used);

        DB::table('leave_balances')->insert([
            'user_id' => $userId,
            'year' => $year,
            'leave_type_id' => $leaveTypeId,
            'allocated_days' => $allocated,
            'used_days' => $used,
            'remaining_days' => $remaining,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) [
            'user_id' => $userId,
            'year' => $year,
            'leave_type_id' => $leaveTypeId,
            'allocated_days' => $allocated,
            'used_days' => $used,
            'remaining_days' => $remaining,
        ];
    }

    private function syncBalancesForPolicyYear(int $year, array $leaveTypeIds = []): void
    {
        $users = DB::table('users')
            ->whereIn('role', ['employee', 'manager', 'hr', 'admin'])
            ->pluck('id');

        $policiesQuery = DB::table('leave_policies')->where('year', $year);
        if ($leaveTypeIds !== []) {
            $policiesQuery->whereIn('leave_type_id', $leaveTypeIds);
        }

        $policies = $policiesQuery->get(['leave_type_id', 'quota_days', 'pro_rata']);

        foreach ($users as $userId) {
            foreach ($policies as $policy) {
                $existing = DB::table('leave_balances')
                    ->where('user_id', $userId)
                    ->where('year', $year)
                    ->where('leave_type_id', $policy->leave_type_id)
                    ->first();

                $allocated = $this->calculatePolicyAllocation(
                    (int) $userId,
                    $year,
                    (float) $policy->quota_days,
                    (bool) $policy->pro_rata
                );
                $used = (float) ($existing->used_days ?? 0);
                $remaining = max(0, $allocated - $used);

                DB::table('leave_balances')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'year' => $year,
                        'leave_type_id' => $policy->leave_type_id,
                    ],
                    [
                        'allocated_days' => $allocated,
                        'used_days' => $used,
                        'remaining_days' => $remaining,
                        'created_at' => $existing->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function adjustLeaveBalanceUsage(int $userId, int $year, int $leaveTypeId, float $deltaUsed): void
    {
        $balance = $this->getOrCreateLeaveBalance($userId, $year, $leaveTypeId);

        $allocated = (float) $balance->allocated_days;
        $used = max(0, (float) $balance->used_days + $deltaUsed);
        $remaining = max(0, $allocated - $used);

        DB::table('leave_balances')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('leave_type_id', $leaveTypeId)
            ->update([
                'used_days' => $used,
                'remaining_days' => $remaining,
                'updated_at' => now(),
            ]);
    }

    public function types(Request $request): JsonResponse
    {
        $year = (int) ($request->query('year', now()->year));

        $rows = DB::table('leave_types')
            ->leftJoin('leave_policies', function ($join) use ($year) {
                $join->on('leave_policies.leave_type_id', '=', 'leave_types.id')
                    ->where('leave_policies.year', '=', $year);
            })
            ->select([
                'leave_types.id',
                'leave_types.name',
                'leave_types.code',
                'leave_types.paid',
                'leave_policies.year',
                'leave_policies.quota_days',
                'leave_policies.pro_rata',
                'leave_policies.carry_forward_days',
            ])
            ->orderBy('leave_types.name')
            ->get();

        return response()->json(['ok' => true, 'year' => $year, 'types' => $rows]);
    }

    public function storeType(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:leave_types,code'],
            'paid' => ['nullable', 'boolean'],
        ]);

        $code = $validated['code'] ?? $this->makeLeaveTypeCode($validated['name']);
        $originalCode = $code;
        $suffix = 1;
        while (DB::table('leave_types')->where('code', $code)->exists()) {
            $code = $originalCode.'_'.$suffix;
            $suffix++;
        }

        DB::table('leave_types')->insert([
            'name' => $validated['name'],
            'code' => $code,
            'paid' => (bool) ($validated['paid'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'code' => $code], 201);
    }

    public function updateType(Request $request, string $code): JsonResponse
    {
        $this->ensureAdmin($request);

        $leaveType = DB::table('leave_types')->where('code', $code)->first();
        if (!$leaveType) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('leave_types', 'code')->ignore($leaveType->id)],
            'paid' => ['nullable', 'boolean'],
        ]);

        DB::table('leave_types')
            ->where('id', $leaveType->id)
            ->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'paid' => (bool) ($validated['paid'] ?? true),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function destroyType(Request $request, string $code): JsonResponse
    {
        $this->ensureAdmin($request);

        $leaveType = DB::table('leave_types')->where('code', $code)->first();
        if (!$leaveType) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $hasUsage = DB::table('leave_requests')->where('leave_type_id', $leaveType->id)->exists()
            || DB::table('leave_balances')->where('leave_type_id', $leaveType->id)->exists()
            || DB::table('leave_policies')->where('leave_type_id', $leaveType->id)->exists();

        if ($hasUsage) {
            return response()->json([
                'ok' => false,
                'message' => 'This leave type is already in use and cannot be deleted.',
            ], 422);
        }

        DB::table('leave_types')->where('id', $leaveType->id)->delete();

        return response()->json(['ok' => true]);
    }

    public function myBalance(Request $request): JsonResponse
    {
        $year = (int) ($request->query('year', now()->year));

        $balances = DB::table('leave_balances')
            ->join('leave_types', 'leave_types.id', '=', 'leave_balances.leave_type_id')
            ->where('leave_balances.user_id', $request->user()->id)
            ->where('leave_balances.year', $year)
            ->select([
                'leave_types.code',
                'leave_types.name',
                'leave_balances.allocated_days',
                'leave_balances.used_days',
                'leave_balances.remaining_days',
            ])
            ->orderBy('leave_types.name')
            ->get()
            ->map(fn ($balance) => [
                'code' => $balance->code,
                'name' => $balance->name,
                'allocated' => (float) ($balance->allocated_days ?? 0),
                'used' => (float) ($balance->used_days ?? 0),
                'remaining' => (float) ($balance->remaining_days ?? 0),
            ])
            ->values();

        return response()->json(['ok' => true, 'year' => $year, 'balances' => $balances]);
    }

    public function employeeBalance(Request $request, string $employeeCode): JsonResponse
    {
        $this->ensureAdmin($request);

        $year = (int) ($request->query('year', now()->year));
        $userId = DB::table('users')->where('employee_code', $employeeCode)->value('id');

        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $balances = DB::table('leave_types')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(function ($leaveType) use ($userId, $year) {
                $balance = $this->getOrCreateLeaveBalance($userId, $year, (int) $leaveType->id);

                return [
                    'code' => $leaveType->code,
                    'name' => $leaveType->name,
                    'allocated' => (float) ($balance->allocated_days ?? 0),
                    'used' => (float) ($balance->used_days ?? 0),
                    'remaining' => (float) ($balance->remaining_days ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'year' => $year,
            'employee_code' => $employeeCode,
            'balances' => $balances,
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_code' => ['required', 'string', 'max:30'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'duration_type' => ['nullable', Rule::in(['full_day', 'half_day'])],
            'half_day_slot' => ['nullable', Rule::in(['first_half', 'second_half'])],
            'daily_breakdown' => ['nullable', 'array'],
            'daily_breakdown.*.date' => ['required_with:daily_breakdown', 'date_format:Y-m-d'],
            'daily_breakdown.*.duration_type' => ['required_with:daily_breakdown', Rule::in(['full_day', 'half_day'])],
            'daily_breakdown.*.half_day_slot' => ['nullable', Rule::in(['first_half', 'second_half'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'handover_to' => ['nullable', 'string', 'max:255'],
        ]);

        $employmentType = DB::table('employee_profiles')
            ->where('user_id', $request->user()->id)
            ->value('employment_type');

        if (
            $employmentType !== null
            && !in_array(strtolower((string) $employmentType), ['permanent', 'contract'], true)
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Only permanent and contract employees can apply for leave.',
            ], 422);
        }

        $durationType = $this->normalizeDurationType($validated['duration_type'] ?? 'full_day');
        $halfDaySlot = $this->normalizeHalfDaySlot($validated['half_day_slot'] ?? null);

        if ($durationType === 'half_day') {
            if ($validated['from_date'] !== $validated['to_date']) {
                return response()->json(['ok' => false, 'message' => 'Half day leave must be for a single date'], 422);
            }

            if (!$halfDaySlot) {
                return response()->json(['ok' => false, 'message' => 'Choose first half or second half for half day leave'], 422);
            }
        }

        $dailyBreakdown = $this->normalizeDailyBreakdownInput($validated['daily_breakdown'] ?? []);
        $leavePlan = $this->buildLeavePlan(
            $validated['from_date'],
            $validated['to_date'],
            $durationType,
            $halfDaySlot,
            $dailyBreakdown
        );

        $leaveType = DB::table('leave_types')
            ->where('code', $validated['leave_type_code'])
            ->first(['id', 'paid']);

        if (!$leaveType) {
            return response()->json(['ok' => false, 'message' => 'Invalid leave type'], 422);
        }

        $overlapsExisting = $this->leaveRequestsOverlap(
            $request->user()->id,
            $validated['from_date'],
            $validated['to_date'],
            $durationType,
            $halfDaySlot,
            $dailyBreakdown,
        );

        if ($overlapsExisting) {
            return response()->json(['ok' => false, 'message' => 'You already have a leave request for the selected dates'], 422);
        }

        $year = (int) date('Y', strtotime($validated['from_date']));
        $requestedDays = $this->calculateRequestedLeaveDaysFromPlan($leavePlan);
        $balance = $this->getOrCreateLeaveBalance($request->user()->id, $year, (int) $leaveType->id);

        if ((bool) $leaveType->paid && $requestedDays > (float) $balance->remaining_days) {
            return response()->json(['ok' => false, 'message' => 'Insufficient leave balance'], 422);
        }

        $code = 'LV-'.Str::upper(Str::random(8));

        $leaveRequestId = DB::table('leave_requests')->insertGetId([
            'code' => $code,
            'user_id' => $request->user()->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'duration_type' => $durationType,
            'half_day_slot' => $halfDaySlot,
            'daily_breakdown' => json_encode($leavePlan),
            'days' => $requestedDays,
            'reason' => $validated['reason'] ?? null,
            'handover_to' => $validated['handover_to'] ?? null,
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Workflow: manager then HR (stored as steps).
        DB::table('leave_approvals')->insert([
            [
                'leave_request_id' => $leaveRequestId,
                'step' => 'manager',
                'reviewer_user_id' => null,
                'status' => 'Pending',
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'leave_request_id' => $leaveRequestId,
                'step' => 'hr',
                'reviewer_user_id' => null,
                'status' => 'Waiting',
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return response()->json(['ok' => true, 'code' => $code], 201);
    }

    public function myRequests(Request $request): JsonResponse
    {
        $rows = DB::table('leave_requests')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.user_id', $request->user()->id)
            ->select([
                'leave_requests.code',
                'leave_requests.status',
                'leave_types.name as leave_type',
                'leave_requests.from_date',
                'leave_requests.to_date',
                'leave_requests.duration_type',
                'leave_requests.half_day_slot',
                'leave_requests.daily_breakdown',
                'leave_requests.days',
                'leave_requests.created_at',
            ])
            ->orderByDesc('leave_requests.created_at')
            ->get();

        $rows->transform(function ($row) {
            $row->daily_breakdown = $this->normalizeDailyBreakdownInput($row->daily_breakdown ?? []);
            return $row;
        });

        return response()->json(['ok' => true, 'requests' => $rows]);
    }

    public function pendingForReview(Request $request): JsonResponse
    {
        // Managers: approvals step=manager status=Pending for their reports
        // HR: approvals step=hr status=Pending or Waiting for any request after manager approval

        $user = $request->user();

        $query = DB::table('leave_requests')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->join('leave_approvals as mgr', function ($join) {
                $join->on('mgr.leave_request_id', '=', 'leave_requests.id')
                    ->where('mgr.step', '=', 'manager');
            })
            ->join('leave_approvals as hr', function ($join) {
                $join->on('hr.leave_request_id', '=', 'leave_requests.id')
                    ->where('hr.step', '=', 'hr');
            })
            ->select([
                'leave_requests.code',
                'leave_requests.status',
                'users.employee_code',
                'users.name as employee_name',
                'leave_types.name as leave_type',
                'leave_requests.from_date',
                'leave_requests.to_date',
                'leave_requests.duration_type',
                'leave_requests.half_day_slot',
                'leave_requests.days',
                'mgr.status as manager_status',
                'hr.status as hr_status',
            ]);

        if ($user->hasPermission('leave.approve_hr')) {
            // HR sees requests where manager approved and HR not finalized
            $query->where('mgr.status', 'Approved')
                ->whereIn('hr.status', ['Pending', 'Waiting']);
        } elseif ($user->hasPermission('leave.approve_manager')) {
            // Manager sees their team requests
            $reportIds = DB::table('reporting_lines')->where('manager_user_id', $user->id)->pluck('user_id');
            $query->whereIn('leave_requests.user_id', $reportIds)
                ->where('mgr.status', 'Pending');
        } else {
            return response()->json(['ok' => true, 'requests' => []]);
        }

        $rows = $query->orderBy('leave_requests.created_at')->get();

        return response()->json(['ok' => true, 'requests' => $rows]);
    }

    public function review(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'step' => ['required', Rule::in(['manager', 'hr'])],
            'status' => ['required', Rule::in(['Approved', 'Rejected'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if ($validated['step'] === 'manager' && !$user->hasPermission('leave.approve_manager')) {
            abort(403);
        }
        if ($validated['step'] === 'hr' && !$user->hasPermission('leave.approve_hr')) {
            abort(403);
        }

        $leaveRequest = DB::table('leave_requests')->where('code', $code)->first();
        if (!$leaveRequest) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        if ($validated['step'] === 'manager') {
            $canReview = DB::table('reporting_lines')
                ->where('user_id', $leaveRequest->user_id)
                ->where('manager_user_id', $user->id)
                ->exists();

            if (!$canReview) {
                abort(403);
            }
        }

        DB::transaction(function () use ($validated, $user, $leaveRequest) {
            $requestRow = DB::table('leave_requests')
                ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
                ->where('leave_requests.id', $leaveRequest->id)
                ->lockForUpdate()
                ->select([
                    'leave_requests.*',
                    'leave_types.name as leave_type_name',
                ])
                ->first();

            if (!$requestRow) {
                throw ValidationException::withMessages(['code' => 'Leave request not found']);
            }

            $finalStatus = $requestRow->status;
            $wasApproved = $requestRow->status === 'Approved';

            DB::table('leave_approvals')
                ->where('leave_request_id', $leaveRequest->id)
                ->where('step', $validated['step'])
                ->update([
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                    'reviewer_user_id' => $user->id,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($validated['step'] === 'manager') {
                // If manager approved, move HR step from Waiting -> Pending
                if ($validated['status'] === 'Approved') {
                    DB::table('leave_approvals')
                        ->where('leave_request_id', $leaveRequest->id)
                        ->where('step', 'hr')
                        ->where('status', 'Waiting')
                        ->update([
                            'status' => 'Pending',
                            'updated_at' => now(),
                        ]);
                } else {
                    $finalStatus = 'Rejected';
                    DB::table('leave_requests')->where('id', $leaveRequest->id)->update([
                        'status' => 'Rejected',
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($validated['step'] === 'hr') {
                $finalStatus = $validated['status'] === 'Approved' ? 'Approved' : 'Rejected';
                DB::table('leave_requests')->where('id', $leaveRequest->id)->update([
                    'status' => $finalStatus,
                    'updated_at' => now(),
                ]);
            }

            if (!$wasApproved && $finalStatus === 'Approved') {
                $year = (int) date('Y', strtotime((string) $requestRow->from_date));
                $this->adjustLeaveBalanceUsage((int) $requestRow->user_id, $year, (int) $requestRow->leave_type_id, (float) $requestRow->days);
                $this->syncAttendanceWithLeaveStatus(
                    (int) $requestRow->user_id,
                    (string) $requestRow->from_date,
                    (string) $requestRow->to_date,
                    true,
                    (string) ($requestRow->duration_type ?? 'full_day'),
                    $requestRow->half_day_slot ? (string) $requestRow->half_day_slot : null,
                    $this->normalizeDailyBreakdownInput($requestRow->daily_breakdown ?? [])
                );
            }

            if ($wasApproved && $finalStatus !== 'Approved') {
                $year = (int) date('Y', strtotime((string) $requestRow->from_date));
                $this->adjustLeaveBalanceUsage((int) $requestRow->user_id, $year, (int) $requestRow->leave_type_id, -1 * (float) $requestRow->days);
                $this->syncAttendanceWithLeaveStatus(
                    (int) $requestRow->user_id,
                    (string) $requestRow->from_date,
                    (string) $requestRow->to_date,
                    false,
                    (string) ($requestRow->duration_type ?? 'full_day'),
                    $requestRow->half_day_slot ? (string) $requestRow->half_day_slot : null,
                    $this->normalizeDailyBreakdownInput($requestRow->daily_breakdown ?? [])
                );
            }

            $title = match ($validated['step']) {
                'manager' => $validated['status'] === 'Approved' ? 'Leave Request Reviewed by Manager' : 'Leave Request Rejected by Manager',
                'hr' => $validated['status'] === 'Approved' ? 'Leave Request Approved' : 'Leave Request Rejected',
                default => 'Leave Request Updated',
            };

            $message = sprintf(
                'Your %s request from %s to %s (%s) is now %s.',
                $requestRow->leave_type_name ?? 'leave',
                (string) $requestRow->from_date,
                (string) $requestRow->to_date,
                $this->formatLeaveDurationLabel(
                    (float) $requestRow->days,
                    (string) ($requestRow->duration_type ?? 'full_day'),
                    $requestRow->half_day_slot ? (string) $requestRow->half_day_slot : null
                ),
                strtolower($finalStatus)
            );

            if ($validated['step'] === 'manager' && $validated['status'] === 'Approved') {
                $message = sprintf(
                    'Your %s request from %s to %s (%s) was approved by your manager and sent to HR for final review.',
                    $requestRow->leave_type_name ?? 'leave',
                    (string) $requestRow->from_date,
                    (string) $requestRow->to_date,
                    $this->formatLeaveDurationLabel(
                        (float) $requestRow->days,
                        (string) ($requestRow->duration_type ?? 'full_day'),
                        $requestRow->half_day_slot ? (string) $requestRow->half_day_slot : null
                    )
                );
            }

            $this->createEmployeeNotification(
                (int) $requestRow->user_id,
                'leave_review',
                $title,
                $message,
                'leave_request',
                (string) $requestRow->code,
                [
                    'step' => $validated['step'],
                    'decision' => $validated['status'],
                    'final_status' => $finalStatus,
                    'reviewer_id' => $user->id,
                ],
            );
        });

        return response()->json(['ok' => true]);
    }

    public function updateEmployeeBalance(Request $request, string $employeeCode): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'mode' => ['nullable', Rule::in(['absolute', 'adjust'])],
            'balances' => ['nullable', 'array'],
        ]);

        $userId = DB::table('users')->where('employee_code', $employeeCode)->value('id');
        if (!$userId) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $year = (int) ($validated['year'] ?? now()->year);
        $mode = $validated['mode'] ?? 'absolute';
        $requestPayload = $request->all();
        $rawBalances = $validated['balances'] ?? [];

        if (!is_array($rawBalances) || $rawBalances === []) {
            $reservedKeys = ['year', 'mode', 'balances'];
            $rawBalances = collect($requestPayload)
                ->except($reservedKeys)
                ->all();
        }

        $requestedBalances = collect($rawBalances)
            ->mapWithKeys(function ($value, $key) {
                if (is_array($value) && isset($value['code']) && array_key_exists('value', $value)) {
                    return [(string) $value['code'] => $value['value']];
                }

                return [(string) $key => $value];
            })
            ->filter(static fn ($value) => $value !== null);

        if ($requestedBalances->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'No balances provided'], 422);
        }

        foreach ($requestedBalances as $code => $value) {
            if (!is_numeric($value)) {
                return response()->json(['ok' => false, 'message' => "Invalid value for leave type: {$code}"], 422);
            }

            if ($mode === 'absolute' && (float) $value < 0) {
                return response()->json(['ok' => false, 'message' => "Negative balance is not allowed for leave type: {$code}"], 422);
            }
        }

        $leaveTypeIds = DB::table('leave_types')
            ->whereIn('code', $requestedBalances->keys()->all())
            ->pluck('id', 'code');

        DB::transaction(function () use ($requestedBalances, $leaveTypeIds, $userId, $year, $mode) {
            foreach ($requestedBalances as $code => $allocatedDays) {
                $leaveTypeId = $leaveTypeIds[$code] ?? null;
                if (!$leaveTypeId) {
                    continue;
                }

                $existing = $this->getOrCreateLeaveBalance($userId, $year, (int) $leaveTypeId);

                $usedDays = (float) ($existing->used_days ?? 0);
                $allocated = (float) $allocatedDays;
                if ($mode === 'adjust') {
                    $allocated = max(0, (float) $existing->allocated_days + $allocated);
                }
                $remaining = max(0, $allocated - $usedDays);

                DB::table('leave_balances')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'year' => $year,
                        'leave_type_id' => $leaveTypeId,
                    ],
                    [
                        'allocated_days' => $allocated,
                        'used_days' => $usedDays,
                        'remaining_days' => $remaining,
                        'created_at' => $existing->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });

        return $this->employeeBalance($request, $employeeCode);
    }

    public function policies(Request $request): JsonResponse
    {
        $year = (int) ($request->query('year', now()->year));

        $policies = DB::table('leave_types')
            ->leftJoin('leave_policies', function ($join) use ($year) {
                $join->on('leave_policies.leave_type_id', '=', 'leave_types.id')
                    ->where('leave_policies.year', '=', $year);
            })
            ->select([
                'leave_types.code',
                'leave_types.name',
                'leave_types.paid',
                'leave_policies.quota_days',
                'leave_policies.pro_rata',
                'leave_policies.carry_forward_days',
            ])
            ->orderBy('leave_types.name')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
                'paid' => (bool) $row->paid,
                'quota_days' => (float) ($row->quota_days ?? 0),
                'pro_rata' => (bool) ($row->pro_rata ?? false),
                'carry_forward_days' => (float) ($row->carry_forward_days ?? 0),
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'year' => $year,
            'policies' => $policies,
        ]);
    }

    public function updatePolicies(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'policies' => ['required', 'array', 'min:1'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $requestedPolicies = collect($validated['policies']);

        $codes = $requestedPolicies->keys()->map(fn ($code) => (string) $code)->values()->all();
        $leaveTypes = DB::table('leave_types')
            ->whereIn('code', $codes)
            ->get(['id', 'code'])
            ->keyBy('code');

        DB::transaction(function () use ($requestedPolicies, $leaveTypes, $year) {
            foreach ($requestedPolicies as $code => $policy) {
                $leaveType = $leaveTypes->get($code);
                if (!$leaveType || !is_array($policy)) {
                    continue;
                }

                $quota = isset($policy['quota_days']) && is_numeric($policy['quota_days'])
                    ? max(0, (float) $policy['quota_days'])
                    : 0;

                $carryForward = isset($policy['carry_forward_days']) && is_numeric($policy['carry_forward_days'])
                    ? max(0, (float) $policy['carry_forward_days'])
                    : 0;

                $proRata = (bool) ($policy['pro_rata'] ?? false);

                DB::table('leave_policies')->updateOrInsert(
                    [
                        'year' => $year,
                        'leave_type_id' => $leaveType->id,
                    ],
                    [
                        'quota_days' => $quota,
                        'pro_rata' => $proRata,
                        'carry_forward_days' => $carryForward,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->syncBalancesForPolicyYear(
                $year,
                $leaveTypes->pluck('id')->map(fn ($id) => (int) $id)->all()
            );
        });

        return $this->policies(new Request(['year' => $year]));
    }
}
