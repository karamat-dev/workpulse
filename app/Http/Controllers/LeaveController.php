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
    private function syncAttendanceWithLeaveStatus(int $userId, string $fromDate, string $toDate, bool $approved): void
    {
        $cursor = now()->parse($fromDate)->startOfDay();
        $end = now()->parse($toDate)->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            if ($approved) {
                DB::table('attendance_days')->updateOrInsert(
                    ['user_id' => $userId, 'date' => $date],
                    [
                        'status' => 'Leave',
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
                    $clockIn = $punches->firstWhere('type', 'clock_in');
                    $clockOut = $punches->firstWhere('type', 'clock_out');
                    $workedMinutes = 0;

                    if ($clockIn && $clockOut) {
                        $clockInAt = now()->parse($clockIn->punched_at);
                        $clockOutAt = now()->parse($clockOut->punched_at);
                        if ($clockOutAt->greaterThan($clockInAt)) {
                            $workedMinutes = $clockInAt->diffInMinutes($clockOutAt);
                        }
                    }

                    $openBreakAt = null;
                    foreach ($punches as $punch) {
                        if ($punch->type === 'break_out') {
                            $openBreakAt = now()->parse($punch->punched_at);
                        }

                        if ($punch->type === 'break_in' && $openBreakAt) {
                            $workedMinutes -= $openBreakAt->diffInMinutes(now()->parse($punch->punched_at));
                            $openBreakAt = null;
                        }
                    }

                    $workedMinutes = max(0, $workedMinutes);
                    $clockInAt = $clockIn ? now()->parse($clockIn->punched_at) : null;
                    $clockOutAt = $clockOut ? now()->parse($clockOut->punched_at) : null;
                    $late = $clockInAt ? $clockInAt->copy()->greaterThan($clockInAt->copy()->setTime(11, 10)) : false;
                    $overtimeMinutes = 0;

                    if ($clockOutAt) {
                        $shiftEnd = $clockOutAt->copy()->setTime(20, 0);
                        if ($clockOutAt->greaterThan($shiftEnd)) {
                            $overtimeMinutes = $shiftEnd->diffInMinutes($clockOutAt);
                        }
                    }

                    DB::table('attendance_days')->updateOrInsert(
                        ['user_id' => $userId, 'date' => $date],
                        [
                            'status' => $clockIn ? 'Present' : 'Absent',
                            'late' => $late,
                            'overtime_minutes' => $overtimeMinutes,
                            'worked_minutes' => $workedMinutes,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            $cursor->addDay();
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

        $allocated = (float) (DB::table('leave_policies')
            ->where('year', $year)
            ->where('leave_type_id', $leaveTypeId)
            ->value('quota_days') ?? 0);

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
            ->get();

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
            ->leftJoin('leave_balances', function ($join) use ($userId, $year) {
                $join->on('leave_balances.leave_type_id', '=', 'leave_types.id')
                    ->where('leave_balances.user_id', '=', $userId)
                    ->where('leave_balances.year', '=', $year);
            })
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
            'days' => ['nullable', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'handover_to' => ['nullable', 'string', 'max:255'],
        ]);

        $leaveType = DB::table('leave_types')
            ->where('code', $validated['leave_type_code'])
            ->first(['id', 'paid']);

        if (!$leaveType) {
            return response()->json(['ok' => false, 'message' => 'Invalid leave type'], 422);
        }

        $overlapsExisting = DB::table('leave_requests')
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where('from_date', '<=', $validated['to_date'])
            ->where('to_date', '>=', $validated['from_date'])
            ->exists();

        if ($overlapsExisting) {
            return response()->json(['ok' => false, 'message' => 'You already have a leave request for the selected dates'], 422);
        }

        $year = (int) date('Y', strtotime($validated['from_date']));
        $requestedDays = (float) ($validated['days'] ?? 1);
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
                'leave_requests.days',
                'leave_requests.created_at',
            ])
            ->orderByDesc('leave_requests.created_at')
            ->get();

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
                ->where('id', $leaveRequest->id)
                ->lockForUpdate()
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
                $this->syncAttendanceWithLeaveStatus((int) $requestRow->user_id, (string) $requestRow->from_date, (string) $requestRow->to_date, true);
            }

            if ($wasApproved && $finalStatus !== 'Approved') {
                $year = (int) date('Y', strtotime((string) $requestRow->from_date));
                $this->adjustLeaveBalanceUsage((int) $requestRow->user_id, $year, (int) $requestRow->leave_type_id, -1 * (float) $requestRow->days);
                $this->syncAttendanceWithLeaveStatus((int) $requestRow->user_id, (string) $requestRow->from_date, (string) $requestRow->to_date, false);
            }
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
        });

        return $this->policies(new Request(['year' => $year]));
    }
}
