<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
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

    public function punch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['clock_in', 'clock_out', 'break_out', 'break_in'])],
            'punched_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $punchedAt = isset($validated['punched_at']) ? now()->parse($validated['punched_at']) : now();
        $date = $punchedAt->toDateString();

        $hasApprovedLeave = DB::table('leave_requests')
            ->where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('from_date', '<=', $date)
            ->where('to_date', '>=', $date)
            ->exists();

        if ($hasApprovedLeave) {
            return response()->json([
                'ok' => false,
                'message' => 'You cannot punch attendance on an approved leave day.',
            ], 422);
        }

        DB::transaction(function () use ($user, $validated, $punchedAt, $date) {
            $existingPunches = DB::table('attendance_punches')
                ->where('user_id', $user->id)
                ->where('date', $date)
                ->orderBy('punched_at')
                ->get();

            $shift = $this->shiftForUser($user->id);

            $this->validatePunchSequence($validated['type'], $existingPunches, $punchedAt, $shift);

            DB::table('attendance_punches')->insert([
                'user_id' => $user->id,
                'date' => $date,
                'type' => $validated['type'],
                'punched_at' => $punchedAt->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $allPunches = DB::table('attendance_punches')
                ->where('user_id', $user->id)
                ->where('date', $date)
                ->orderBy('punched_at')
                ->get();

            $summary = $this->buildAttendanceDaySummary($allPunches, $shift);

            $day = DB::table('attendance_days')->where('user_id', $user->id)->where('date', $date)->first();

            DB::table('attendance_days')->updateOrInsert(
                ['user_id' => $user->id, 'date' => $date],
                [
                    'status' => $summary['status'],
                    'late' => $summary['late'],
                    'overtime_minutes' => $summary['overtime_minutes'],
                    'worked_minutes' => $summary['worked_minutes'],
                    'created_at' => $day?->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        });

        return response()->json([
            'ok' => true,
            'date' => $date,
        ]);
    }

    private function shiftEndAt(string $date, ?object $shift = null)
    {
        $shiftStart = $shift?->start_time ? now()->parse($date.' '.(string) $shift->start_time) : now()->parse($date.' 11:00:00');
        $shiftEnd = $shift?->end_time ? now()->parse($date.' '.(string) $shift->end_time) : now()->parse($date.' 20:00:00');

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        return $shiftEnd;
    }

    private function punchSessionState($punches): array
    {
        $clockInCount = $punches->where('type', 'clock_in')->count();
        $clockOutCount = $punches->where('type', 'clock_out')->count();
        $breakOutCount = $punches->where('type', 'break_out')->count();
        $breakInCount = $punches->where('type', 'break_in')->count();

        $activeClockIn = $clockInCount > $clockOutCount;
        $onBreak = $activeClockIn && $breakOutCount > $breakInCount;

        return [
            'activeClockIn' => $activeClockIn,
            'onBreak' => $onBreak,
        ];
    }

    private function validatePunchSequence(string $type, $punches, $punchedAt, ?object $shift = null): void
    {
        $state = $this->punchSessionState($punches);
        $shiftEnded = $punchedAt->greaterThanOrEqualTo($this->shiftEndAt($punchedAt->toDateString(), $shift));

        if ($type === 'clock_in' && $state['activeClockIn']) {
            throw ValidationException::withMessages(['type' => 'You are already clocked in.']);
        }

        if ($type === 'clock_in' && $shiftEnded) {
            throw ValidationException::withMessages(['type' => 'Shift is already completed for today.']);
        }

        if ($type === 'clock_out' && !$state['activeClockIn']) {
            throw ValidationException::withMessages(['type' => 'You need an active clock-in before clocking out.']);
        }

        if ($type === 'break_out' && (!$state['activeClockIn'] || $state['onBreak'])) {
            throw ValidationException::withMessages(['type' => 'Break out is only allowed while you are clocked in and not already on break.']);
        }

        if ($type === 'break_in' && (!$state['activeClockIn'] || !$state['onBreak'])) {
            throw ValidationException::withMessages(['type' => 'Break in is only allowed after an open break.']);
        }
    }

    private function shiftForUser(int $userId): ?object
    {
        return DB::table('employee_profiles')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->where('employee_profiles.user_id', $userId)
            ->select([
                'shifts.start_time',
                'shifts.end_time',
                'shifts.grace_minutes',
            ])
            ->first();
    }

    private function buildAttendanceDaySummary($punches, ?object $shift = null): array
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
        $shiftStart = $shift?->start_time ? now()->parse((string) $shift->start_time) : now()->parse('11:00:00');
        $shiftEnd = $shift?->end_time ? now()->parse((string) $shift->end_time) : now()->parse('20:00:00');
        $graceMinutes = (int) ($shift?->grace_minutes ?? 10);
        $lateCutoff = $clockInAt->copy()->setTime((int) $shiftStart->format('H'), (int) $shiftStart->format('i'));
        $lateCutoff->addMinutes($graceMinutes);
        $late = $clockInAt->greaterThan($lateCutoff);
        $overtimeMinutes = 0;

        if ($lastClockOutAt) {
            $scheduledShiftEnd = $lastClockOutAt->copy()->setTime((int) $shiftEnd->format('H'), (int) $shiftEnd->format('i'));
            if ($lastClockOutAt->greaterThan($scheduledShiftEnd)) {
                $overtimeMinutes = $scheduledShiftEnd->diffInMinutes($lastClockOutAt);
            }
        }

        return [
            'status' => 'Present',
            'late' => $late,
            'worked_minutes' => $workedMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ];
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        // Base: all employee users
        $users = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->whereIn('users.role', ['employee', 'hr', 'admin'])
            ->select([
                'users.id',
                'users.employee_code',
                'users.name',
                'users.role',
                'departments.name as department',
                'employee_profiles.designation',
            ])
            ->orderBy('users.employee_code')
            ->get();

        $days = DB::table('attendance_days')
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        $punches = DB::table('attendance_punches')
            ->where('date', $date)
            ->orderBy('punched_at')
            ->get()
            ->groupBy('user_id');

        $regulations = DB::table('attendance_regulation_requests')
            ->where('date', $date)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        $leaveRequests = DB::table('leave_requests')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.from_date', '<=', $date)
            ->where('leave_requests.to_date', '>=', $date)
            ->select([
                'leave_requests.user_id',
                'leave_requests.code',
                'leave_requests.status',
                'leave_types.name as leave_type',
                'leave_requests.from_date',
                'leave_requests.to_date',
                'leave_requests.days',
            ])
            ->get()
            ->groupBy('user_id');

        $rows = $users->map(function ($u) use ($days, $punches, $regulations, $leaveRequests, $date) {
            $day = $days->get($u->id);
            $userPunches = $punches->get($u->id, collect());

            $clockIn = optional($userPunches->firstWhere('type', 'clock_in'))->punched_at;
            $clockOut = optional($userPunches->where('type', 'clock_out')->last())->punched_at;
            $breakOut = optional($userPunches->where('type', 'break_out')->last())->punched_at;
            $breakIn = optional($userPunches->where('type', 'break_in')->last())->punched_at;

            $leave = $leaveRequests->get($u->id, collect())->first();

            $status = $day?->status ?? 'Absent';
            if ($leave) {
                $status = 'Leave';
            }

            return [
                'date' => $date,
                'employee_code' => $u->employee_code,
                'name' => $u->name,
                'department' => $u->department,
                'designation' => $u->designation,
                'status' => $status,
                'punches' => [
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_out' => $breakOut,
                    'break_in' => $breakIn,
                ],
                'late' => (bool) ($day?->late ?? false),
                'overtime_minutes' => (int) ($day?->overtime_minutes ?? 0),
                'worked_minutes' => (int) ($day?->worked_minutes ?? 0),
                'leave' => $leave ? [
                    'code' => $leave->code,
                    'type' => $leave->leave_type,
                    'status' => $leave->status,
                    'from' => $leave->from_date,
                    'to' => $leave->to_date,
                    'days' => (float) $leave->days,
                ] : null,
                'regulations' => $regulations->get($u->id, collect())->values(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'date' => $date,
            'rows' => $rows,
        ]);
    }

    public function dailyReportCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $response = $this->dailyReport(new Request(['date' => $date]));
        $payload = $response->getData(true);
        $rows = $payload['rows'] ?? [];
        $filename = 'attendance-daily-'.$date.'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Employee ID', 'Name', 'Department', 'Designation', 'Status', 'Clock In', 'Break Out', 'Break In', 'Clock Out', 'Worked Minutes', 'Overtime Minutes', 'Late']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['date'] ?? '',
                    $row['employee_code'] ?? '',
                    $row['name'] ?? '',
                    $row['department'] ?? '',
                    $row['designation'] ?? '',
                    $row['status'] ?? '',
                    $row['punches']['clock_in'] ?? '',
                    $row['punches']['break_out'] ?? '',
                    $row['punches']['break_in'] ?? '',
                    $row['punches']['clock_out'] ?? '',
                    $row['worked_minutes'] ?? 0,
                    $row['overtime_minutes'] ?? 0,
                    !empty($row['late']) ? 'Yes' : 'No',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function liveStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $employeesQuery = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->whereIn('users.role', ['employee', 'hr', 'admin'])
            ->select([
                'users.id as user_id',
                'users.employee_code',
                'users.name',
                'departments.name as dept',
            ])
            ->orderBy('users.employee_code');

        if ($user->role === 'employee') {
            $teamUserIds = DB::table('reporting_lines')->where('manager_user_id', $user->id)->pluck('user_id');
            $employeesQuery->where(function ($q) use ($user, $teamUserIds) {
                $q->where('users.id', $user->id)->orWhereIn('users.id', $teamUserIds);
            });
        }

        $employees = $employeesQuery->get()->map(function ($employee) {
            $parts = preg_split('/\s+/', trim((string) $employee->name)) ?: [];
            $fname = $parts[0] ?? $employee->name;
            $lname = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

            return [
                'id' => $employee->employee_code,
                'name' => trim($fname.' '.$lname),
                'dept' => $employee->dept ?? '-',
            ];
        })->values();

        $attendancePunches = DB::table('attendance_punches')
            ->join('users', 'users.id', '=', 'attendance_punches.user_id')
            ->where('attendance_punches.date', $today)
            ->select([
                'users.employee_code as emp_id',
                'attendance_punches.type',
                'attendance_punches.punched_at',
            ])
            ->orderBy('attendance_punches.punched_at')
            ->get()
            ->groupBy('emp_id');

        $leaveByEmployeeToday = DB::table('leave_requests')
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.status', 'Approved')
            ->where('leave_requests.from_date', '<=', $today)
            ->where('leave_requests.to_date', '>=', $today)
            ->select([
                'users.employee_code as empId',
                'leave_types.name as leave_type',
            ])
            ->get()
            ->groupBy('empId');

        $liveAttendance = $employees->map(function (array $employee) use ($attendancePunches, $leaveByEmployeeToday) {
            $empId = $employee['id'];

            if ($leaveByEmployeeToday->has($empId)) {
                $leave = $leaveByEmployeeToday->get($empId)?->first();

                return [
                    'empId' => $empId,
                    'name' => $employee['name'],
                    'dept' => $employee['dept'],
                    'status' => 'leave',
                    'since' => $leave->leave_type ?? 'Approved Leave',
                    'clockIn' => null,
                    'clockOut' => null,
                ];
            }

            $todayPunches = $attendancePunches->get($empId, collect());
            $clockInPunch = $todayPunches->firstWhere('type', 'clock_in');
            $lastClockInPunch = $todayPunches->where('type', 'clock_in')->last();
            $clockOutPunch = $todayPunches->where('type', 'clock_out')->last();
            $state = $this->punchSessionState($todayPunches);

            $clockInTime = $clockInPunch?->punched_at ? now()->parse($clockInPunch->punched_at)->format('H:i') : null;
            $activeClockInTime = $lastClockInPunch?->punched_at ? now()->parse($lastClockInPunch->punched_at)->format('H:i') : null;
            $clockOutTime = $clockOutPunch?->punched_at ? now()->parse($clockOutPunch->punched_at)->format('H:i') : null;

            $status = 'not_checked_in';
            $since = 'Not Checked In';

            if ($state['activeClockIn']) {
                $status = $state['onBreak'] ? 'break' : 'in';
                $since = $activeClockInTime ?? $clockInTime ?? '-';
            } elseif ($clockOutPunch) {
                $status = 'out';
                $since = $clockOutTime ?? '-';
            }

            return [
                'empId' => $empId,
                'name' => $employee['name'],
                'dept' => $employee['dept'],
                'status' => $status,
                'since' => $since,
                'clockIn' => $clockInTime,
                'clockOut' => $clockOutTime,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'date' => $today,
            'liveAttendance' => $liveAttendance,
        ]);
    }

    public function createRegulation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'max:50'],
            'original_value' => ['nullable', 'string', 'max:255'],
            'requested_value' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $code = 'REG-'.Str::upper(Str::random(8));

        DB::table('attendance_regulation_requests')->insert([
            'code' => $code,
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'type' => $validated['type'],
            'original_value' => $validated['original_value'] ?? null,
            'requested_value' => $validated['requested_value'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'code' => $code], 201);
    }

    public function reviewRegulation(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Approved', 'Rejected'])],
        ]);

        $reviewer = $request->user();
        $regulation = DB::table('attendance_regulation_requests')
            ->where('code', $code)
            ->first();

        if (!$regulation) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        DB::transaction(function () use ($validated, $reviewer, $regulation) {
            DB::table('attendance_regulation_requests')
                ->where('id', $regulation->id)
                ->update([
                    'status' => $validated['status'],
                    'reviewer_user_id' => $reviewer->id,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            $decision = strtolower($validated['status']);
            $this->createEmployeeNotification(
                (int) $regulation->user_id,
                'regulation_review',
                $validated['status'] === 'Approved' ? 'Attendance Request Approved' : 'Attendance Request Rejected',
                sprintf(
                    'Your %s request for %s was %s.',
                    (string) $regulation->type,
                    (string) $regulation->date,
                    $decision
                ),
                'attendance_regulation',
                (string) $regulation->code,
                [
                    'decision' => $validated['status'],
                    'reviewer_id' => $reviewer->id,
                    'date' => $regulation->date,
                ],
            );
        });

        return response()->json(['ok' => true]);
    }

    public function destroyRegulation(Request $request, string $code): JsonResponse
    {
        $deleted = DB::table('attendance_regulation_requests')
            ->where('code', $code)
            ->where('user_id', $request->user()->id)
            ->where('status', 'Pending')
            ->delete();

        if ($deleted === 0) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        return response()->json(['ok' => true]);
    }
}
