<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkpulseBootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = DB::table('employee_profiles')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->where('employee_profiles.user_id', $user->id)
            ->select([
                'departments.name as dept_name',
                'employee_profiles.designation',
                'employee_profiles.date_of_joining',
                'employee_profiles.probation_end_date',
                'employee_profiles.last_working_date',
                'employee_profiles.status',
                'employee_profiles.employment_type',
                'mgr.name as manager_name',
                'employee_profiles.personal_phone',
                'employee_profiles.cnic_document_path',
                'employee_profiles.cnic_document_name',
                'shifts.id as shift_id',
                'shifts.code as shift_code',
                'shifts.name as shift_name',
                'shifts.start_time',
                'shifts.end_time',
                'shifts.grace_minutes',
                'shifts.working_days',
            ])
            ->first();

        $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $fname = $nameParts[0] ?? $user->name;
        $lname = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        $avatar = strtoupper(mb_substr($fname, 0, 1).mb_substr($lname ?: $fname, 0, 1));
        $colors = ['#2447D0', '#1B7A42', '#0D7373', '#A05C00', '#C0392B', '#6B3FA0'];
        $avatarColor = $colors[($user->id ?? 1) % count($colors)];

        $currentUser = [
            'id' => $user->employee_code ?? (string) $user->id,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $user->email,
            'pass' => null,
            'role' => $user->role,
            'dept' => $profile?->dept_name ?? '-',
            'desg' => $profile?->designation ?? ($user->role === 'admin' ? 'Administrator' : 'Employee'),
            'doj' => $profile?->date_of_joining,
            'dop' => $profile?->probation_end_date,
            'lwd' => $profile?->last_working_date,
            'manager' => $profile?->manager_name ?? '-',
            'shiftId' => $profile?->shift_id,
            'shiftCode' => $profile?->shift_code,
            'shiftName' => $profile?->shift_name,
            'shiftStart' => $profile?->start_time ? substr((string) $profile->start_time, 0, 5) : null,
            'shiftEnd' => $profile?->end_time ? substr((string) $profile->end_time, 0, 5) : null,
            'shiftGrace' => $profile?->grace_minutes !== null ? (int) $profile->grace_minutes : null,
            'shiftWorkingDays' => $profile?->working_days,
            'phone' => $profile?->personal_phone,
            'cnicDocumentPath' => $profile?->cnic_document_path,
            'cnicDocumentName' => $profile?->cnic_document_name,
            'cnicDocumentUrl' => $profile?->cnic_document_path ? asset($profile->cnic_document_path) : null,
            'avatar' => $avatar,
            'avatarColor' => $avatarColor,
            'status' => $profile?->status ?? 'Active',
            'type' => $profile?->employment_type,
        ];

        $employeesQuery = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('users as mgr', 'mgr.id', '=', 'employee_profiles.manager_user_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->whereIn('users.role', ['employee', 'hr', 'admin'])
            ->select([
                'users.id as user_id',
                'users.employee_code',
                'users.name',
                'users.email',
                'departments.name as dept',
                'employee_profiles.designation as desg',
                'employee_profiles.date_of_joining as doj',
                'employee_profiles.probation_end_date as dop',
                'employee_profiles.last_working_date as lwd',
                'employee_profiles.status',
                'employee_profiles.employment_type as type',
                'mgr.name as manager',
                'employee_profiles.personal_phone as phone',
                'employee_profiles.cnic_document_path as cnic_document_path',
                'employee_profiles.cnic_document_name as cnic_document_name',
                'shifts.id as shift_id',
                'shifts.code as shift_code',
                'shifts.name as shift_name',
                'shifts.start_time',
                'shifts.end_time',
                'shifts.grace_minutes',
                'shifts.working_days',
            ])
            ->orderBy('users.employee_code');

        if ($user->role === 'employee') {
            $teamUserIds = DB::table('reporting_lines')->where('manager_user_id', $user->id)->pluck('user_id');
            $employeesQuery->where(function ($q) use ($user, $teamUserIds) {
                $q->where('users.id', $user->id)->orWhereIn('users.id', $teamUserIds);
            });
        }

        $employees = $employeesQuery->get()->map(function ($employee) use ($colors) {
            $parts = preg_split('/\s+/', trim((string) $employee->name)) ?: [];
            $fn = $parts[0] ?? $employee->name;
            $ln = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
            $av = strtoupper(mb_substr($fn, 0, 1).mb_substr($ln ?: $fn, 0, 1));
            $color = $colors[crc32((string) $employee->employee_code) % count($colors)];

            return [
                'id' => $employee->employee_code,
                'userId' => $employee->user_id,
                'fname' => $fn,
                'lname' => $ln,
                'dept' => $employee->dept ?? '-',
                'desg' => $employee->desg ?? '-',
                'doj' => $employee->doj,
                'dop' => $employee->dop,
                'lwd' => $employee->lwd,
                'manager' => $employee->manager ?? '-',
                'phone' => $employee->phone,
                'email' => $employee->email,
                'shiftId' => $employee->shift_id,
                'shiftCode' => $employee->shift_code,
                'shiftName' => $employee->shift_name,
                'shiftStart' => $employee->start_time ? substr((string) $employee->start_time, 0, 5) : null,
                'shiftEnd' => $employee->end_time ? substr((string) $employee->end_time, 0, 5) : null,
                'shiftGrace' => $employee->grace_minutes !== null ? (int) $employee->grace_minutes : null,
                'shiftWorkingDays' => $employee->working_days,
                'cnicDocumentPath' => $employee->cnic_document_path,
                'cnicDocumentName' => $employee->cnic_document_name,
                'cnicDocumentUrl' => $employee->cnic_document_path ? asset($employee->cnic_document_path) : null,
                'avatar' => $av,
                'avatarColor' => $color,
                'status' => $employee->status ?? 'Active',
                'type' => $employee->type ?? null,
            ];
        })->values();

        $departments = DB::table('departments')
            ->leftJoin('users as head', 'head.id', '=', 'departments.head_user_id')
            ->select([
                'departments.name',
                'departments.color',
                'head.name as head',
            ])
            ->orderBy('departments.name')
            ->get()
            ->map(fn ($department) => [
                'name' => $department->name,
                'head' => $department->head ?? '-',
                'color' => $department->color ?? '#2447D0',
                'count' => 0,
                'present' => 0,
                'leave' => 0,
                'absent' => 0,
            ]);

        $shifts = DB::table('shifts')
            ->orderBy('name')
            ->get()
            ->map(fn ($shift) => [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'start' => substr((string) $shift->start_time, 0, 5),
                'end' => substr((string) $shift->end_time, 0, 5),
                'grace' => (int) $shift->grace_minutes,
                'workingDays' => $shift->working_days,
                'active' => (bool) $shift->is_active,
            ])
            ->values();

        $today = now()->toDateString();
        $attendanceStartDate = now()->subDays(90)->toDateString();

        $attendancePunches = DB::table('attendance_punches')
            ->join('users', 'users.id', '=', 'attendance_punches.user_id')
            ->where('attendance_punches.date', '>=', $attendanceStartDate)
            ->select([
                'users.employee_code as emp_id',
                'attendance_punches.date',
                'attendance_punches.type',
                'attendance_punches.punched_at',
            ])
            ->orderBy('attendance_punches.punched_at')
            ->get()
            ->groupBy(fn ($punch) => $punch->emp_id.'|'.$punch->date);

        $attendance = DB::table('attendance_days')
            ->join('users', 'users.id', '=', 'attendance_days.user_id')
            ->where('attendance_days.date', '>=', $attendanceStartDate)
            ->select([
                'users.employee_code as emp_id',
                'attendance_days.date',
                'attendance_days.status',
                'attendance_days.late',
                'attendance_days.worked_minutes',
                'attendance_days.overtime_minutes',
            ])
            ->orderByDesc('attendance_days.date')
            ->get()
            ->map(function ($day) use ($attendancePunches) {
                $punches = $attendancePunches->get($day->emp_id.'|'.$day->date, collect());

                return [
                    'empId' => $day->emp_id,
                    'date' => $day->date,
                    'in' => $punches->firstWhere('type', 'clock_in')?->punched_at ? now()->parse($punches->firstWhere('type', 'clock_in')->punched_at)->format('H:i') : null,
                    'out' => $punches->firstWhere('type', 'clock_out')?->punched_at ? now()->parse($punches->firstWhere('type', 'clock_out')->punched_at)->format('H:i') : null,
                    'breakOut' => $punches->firstWhere('type', 'break_out')?->punched_at ? now()->parse($punches->firstWhere('type', 'break_out')->punched_at)->format('H:i') : null,
                    'breakIn' => $punches->firstWhere('type', 'break_in')?->punched_at ? now()->parse($punches->firstWhere('type', 'break_in')->punched_at)->format('H:i') : null,
                    'status' => $day->status,
                    'late' => (bool) $day->late,
                    'workedMinutes' => (int) $day->worked_minutes,
                    'overtime' => (int) $day->overtime_minutes,
                ];
            })
            ->values();

        $regulations = DB::table('attendance_regulation_requests')
            ->join('users', 'users.id', '=', 'attendance_regulation_requests.user_id')
            ->select([
                'attendance_regulation_requests.code as id',
                'users.employee_code as empId',
                'attendance_regulation_requests.date',
                'attendance_regulation_requests.type',
                'attendance_regulation_requests.original_value as orig',
                'attendance_regulation_requests.requested_value as req',
                'attendance_regulation_requests.reason',
                'attendance_regulation_requests.status',
            ])
            ->orderByDesc('attendance_regulation_requests.created_at')
            ->limit(200)
            ->get()
            ->map(fn ($regulation) => [
                'id' => $regulation->id,
                'empId' => $regulation->empId,
                'date' => $regulation->date,
                'type' => $regulation->type,
                'orig' => $regulation->orig ?? '-',
                'req' => $regulation->req ?? '-',
                'reason' => $regulation->reason,
                'status' => $regulation->status,
            ])
            ->values();

        $leaves = DB::table('leave_requests')
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->leftJoin('leave_approvals as mgr', function ($join) {
                $join->on('mgr.leave_request_id', '=', 'leave_requests.id')
                    ->where('mgr.step', '=', 'manager');
            })
            ->leftJoin('leave_approvals as hr', function ($join) {
                $join->on('hr.leave_request_id', '=', 'leave_requests.id')
                    ->where('hr.step', '=', 'hr');
            })
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->select([
                'leave_requests.code as id',
                'users.employee_code as empId',
                'users.name as empName',
                'departments.name as dept',
                'leave_types.name as type',
                'leave_requests.from_date as from_date',
                'leave_requests.to_date as to_date',
                'leave_requests.days',
                'leave_requests.reason',
                'leave_requests.handover_to as handover',
                'leave_requests.created_at as applied_at',
                'mgr.status as manager_status',
                'hr.status as hr_status',
                'leave_requests.status',
            ])
            ->orderByDesc('leave_requests.created_at')
            ->limit(200)
            ->get()
            ->map(fn ($leave) => [
                'id' => $leave->id,
                'empId' => $leave->empId,
                'empName' => $leave->empName,
                'dept' => $leave->dept ?? '-',
                'type' => $leave->type,
                'from' => $leave->from_date,
                'to' => $leave->to_date,
                'days' => (float) $leave->days,
                'reason' => $leave->reason,
                'handover' => $leave->handover,
                'applied' => optional($leave->applied_at)->toDateString() ?? null,
                'managerStatus' => $leave->manager_status ?? '-',
                'hrStatus' => $leave->hr_status ?? '-',
                'status' => $leave->status,
            ])
            ->values();

        $leaveBalances = DB::table('leave_balances')
            ->join('leave_types', 'leave_types.id', '=', 'leave_balances.leave_type_id')
            ->where('leave_balances.user_id', $user->id)
            ->where('leave_balances.year', now()->year)
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
                'allocated' => (float) $balance->allocated_days,
                'used' => (float) $balance->used_days,
                'remaining' => (float) $balance->remaining_days,
            ])
            ->values();

        $leaveTypes = DB::table('leave_types')
            ->orderBy('name')
            ->get(['name', 'code', 'paid'])
            ->map(fn ($leaveType) => [
                'name' => $leaveType->name,
                'code' => $leaveType->code,
                'paid' => (bool) $leaveType->paid,
            ])
            ->values();

        $leavePolicies = DB::table('leave_types')
            ->leftJoin('leave_policies', function ($join) {
                $join->on('leave_policies.leave_type_id', '=', 'leave_types.id')
                    ->where('leave_policies.year', '=', now()->year);
            })
            ->orderBy('leave_types.name')
            ->get([
                'leave_types.name',
                'leave_types.code',
                'leave_types.paid',
                'leave_policies.quota_days',
                'leave_policies.pro_rata',
                'leave_policies.carry_forward_days',
            ])
            ->map(fn ($policy) => [
                'name' => $policy->name,
                'code' => $policy->code,
                'paid' => (bool) $policy->paid,
                'quota_days' => (float) ($policy->quota_days ?? 0),
                'pro_rata' => (bool) ($policy->pro_rata ?? false),
                'carry_forward_days' => (float) ($policy->carry_forward_days ?? 0),
            ])
            ->values();

        $attendanceByEmployeeToday = $attendance->where('date', $today)->groupBy('empId');
        $leaveByEmployeeToday = $leaves
            ->filter(fn (array $leave) => $leave['status'] === 'Approved' && $leave['from'] <= $today && $leave['to'] >= $today)
            ->groupBy('empId');

        $liveAttendance = $employees->map(function (array $employee) use ($attendancePunches, $leaveByEmployeeToday, $today) {
            $empId = $employee['id'];

            if ($leaveByEmployeeToday->has($empId)) {
                $leave = $leaveByEmployeeToday->get($empId)?->first();

                return [
                    'empId' => $empId,
                    'name' => trim(($employee['fname'] ?? '').' '.($employee['lname'] ?? '')),
                    'dept' => $employee['dept'] ?? '-',
                    'status' => 'leave',
                    'since' => $leave['type'] ?? 'Approved Leave',
                    'clockIn' => null,
                    'clockOut' => null,
                ];
            }

            $todayPunches = $attendancePunches->get($empId.'|'.$today, collect());
            $clockInPunch = $todayPunches->firstWhere('type', 'clock_in');
            $clockOutPunch = $todayPunches->firstWhere('type', 'clock_out');
            $breakOutCount = $todayPunches->where('type', 'break_out')->count();
            $breakInCount = $todayPunches->where('type', 'break_in')->count();
            $onBreak = $breakOutCount > $breakInCount;

            $clockInTime = $clockInPunch?->punched_at ? now()->parse($clockInPunch->punched_at)->format('H:i') : null;
            $clockOutTime = $clockOutPunch?->punched_at ? now()->parse($clockOutPunch->punched_at)->format('H:i') : null;

            $status = 'not_checked_in';
            $since = 'Not Checked In';

            if ($clockInPunch && !$clockOutPunch) {
                $status = $onBreak ? 'break' : 'in';
                $since = $clockInTime ?? '-';
            } elseif ($clockOutPunch) {
                $status = 'out';
                $since = $clockOutTime ?? '-';
            }

            return [
                'empId' => $empId,
                'name' => trim(($employee['fname'] ?? '').' '.($employee['lname'] ?? '')),
                'dept' => $employee['dept'] ?? '-',
                'status' => $status,
                'since' => $since,
                'clockIn' => $clockInTime,
                'clockOut' => $clockOutTime,
            ];
        })->values();

        $departments = $departments->map(function (array $department) use ($employees, $attendanceByEmployeeToday, $leaveByEmployeeToday) {
            $departmentEmployees = $employees->where('dept', $department['name']);
            $count = $departmentEmployees->count();
            $present = 0;
            $leave = 0;
            $absent = 0;

            foreach ($departmentEmployees as $employee) {
                if ($leaveByEmployeeToday->has($employee['id'])) {
                    $leave++;
                    continue;
                }

                $attendanceRecord = $attendanceByEmployeeToday->get($employee['id'])?->first();
                if (($attendanceRecord['status'] ?? null) === 'Present') {
                    $present++;
                } else {
                    $absent++;
                }
            }

            $department['count'] = $count;
            $department['present'] = $present;
            $department['leave'] = $leave;
            $department['absent'] = $absent;

            return $department;
        })->values();

        $visibleAnnouncementIds = DB::table('announcements')
            ->leftJoin('announcement_recipients', 'announcement_recipients.announcement_id', '=', 'announcements.id')
            ->when($user->role !== 'admin', function ($query) use ($user, $profile) {
                $query->where(function ($audienceQuery) use ($user, $profile) {
                    $audienceQuery
                        ->where('announcements.audience', 'all')
                        ->orWhere('announcements.audience', 'role:'.$user->role)
                        ->orWhere('announcements.audience', 'department:'.($profile?->dept_name ?? ''))
                        ->orWhere(function ($specificQuery) use ($user) {
                            $specificQuery
                                ->where('announcements.audience', 'specific')
                                ->where('announcement_recipients.user_id', $user->id);
                        });
                });
            })
            ->distinct()
            ->pluck('announcements.id');

        $announcementRecipientMap = DB::table('announcement_recipients')
            ->join('users', 'users.id', '=', 'announcement_recipients.user_id')
            ->whereIn('announcement_recipients.announcement_id', $visibleAnnouncementIds->all())
            ->select([
                'announcement_recipients.announcement_id',
                'users.name',
                'users.employee_code',
            ])
            ->get()
            ->groupBy('announcement_id');

        $announcements = DB::table('announcements')
            ->join('users', 'users.id', '=', 'announcements.author_user_id')
            ->select([
                'announcements.id',
                'announcements.title',
                'announcements.category as cat',
                'announcements.audience',
                'announcements.message as msg',
                'users.name as author',
                'users.role',
                'announcements.published_on as date',
            ])
            ->whereIn('announcements.id', $visibleAnnouncementIds->all())
            ->orderByDesc('announcements.published_on')
            ->limit(50)
            ->get()
            ->map(function ($announcement) use ($announcementRecipientMap) {
                $recipients = $announcementRecipientMap->get($announcement->id, collect())
                    ->map(fn ($recipient) => [
                        'employeeCode' => $recipient->employee_code,
                        'name' => $recipient->name,
                    ])
                    ->values();

                $audienceLabel = match (true) {
                    $announcement->audience === 'all' => 'All Employees',
                    str_starts_with((string) $announcement->audience, 'role:') => 'Role: '.ucfirst(substr((string) $announcement->audience, 5)),
                    str_starts_with((string) $announcement->audience, 'department:') => 'Department: '.substr((string) $announcement->audience, 11),
                    $announcement->audience === 'specific' => 'Specific Employees',
                    default => $announcement->audience,
                };

                return [
                    'id' => 'AN-'.$announcement->id,
                    'title' => $announcement->title,
                    'cat' => $announcement->cat,
                    'audience' => $audienceLabel,
                    'audienceKey' => $announcement->audience,
                    'msg' => $announcement->msg,
                    'author' => $announcement->author,
                    'role' => $announcement->role,
                    'date' => $announcement->date,
                    'recipients' => $recipients,
                ];
            })
            ->values();

        $holidays = DB::table('holidays')
            ->orderBy('date')
            ->get(['date', 'name', 'type'])
            ->map(fn ($holiday) => ['date' => $holiday->date, 'name' => $holiday->name, 'type' => $holiday->type])
            ->values();

        $events = DB::table('events')
            ->orderBy('starts_at')
            ->limit(200)
            ->get(['id', 'title', 'description', 'starts_at', 'ends_at', 'type'])
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'desc' => $event->description,
                'start' => $event->starts_at,
                'end' => $event->ends_at,
                'type' => $event->type,
            ])
            ->values();

        $company = DB::table('company_settings')->where('id', 1)->first();

        return response()->json([
            'ok' => true,
            'currentUser' => $currentUser,
            'currentRole' => $user->role,
            'employees' => $employees,
            'departments' => $departments,
            'shifts' => $shifts,
            'attendance' => $attendance,
            'liveAttendance' => $liveAttendance,
            'leaves' => $leaves,
            'leaveTypes' => $leaveTypes,
            'leavePolicies' => $leavePolicies,
            'leaveBalances' => $leaveBalances,
            'regulations' => $regulations,
            'announcements' => $announcements,
            'holidays' => $holidays,
            'events' => $events,
            'company' => $company,
        ]);
    }
}
