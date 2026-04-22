<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private function buildAttendanceStatusMap(string $start, string $end)
    {
        $dayRows = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('attendance_days', function ($join) use ($start, $end) {
                $join->on('attendance_days.user_id', '=', 'users.id')
                    ->whereBetween('attendance_days.date', [$start, $end]);
            })
            ->whereIn('users.role', ['employee', 'manager', 'hr', 'admin'])
            ->select([
                'users.id as user_id',
                'users.employee_code',
                'users.name',
                'departments.name as department',
                'employee_profiles.designation',
                'attendance_days.date',
                'attendance_days.status',
                'attendance_days.late',
                'attendance_days.overtime_minutes',
            ])
            ->orderBy('users.employee_code')
            ->get();

        $leaveRows = DB::table('leave_requests')
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->where('leave_requests.status', 'Approved')
            ->where('leave_requests.from_date', '<=', $end)
            ->where('leave_requests.to_date', '>=', $start)
            ->select([
                'leave_requests.user_id',
                'leave_requests.from_date',
                'leave_requests.to_date',
            ])
            ->get();

        $days = collect();
        $cursor = now()->parse($start)->startOfDay();
        $endDate = now()->parse($end)->startOfDay();
        while ($cursor->lte($endDate)) {
            $days->push($cursor->toDateString());
            $cursor->addDay();
        }

        $leaveByUserDate = [];
        foreach ($leaveRows as $leave) {
            $cursor = now()->parse($leave->from_date)->startOfDay();
            $leaveEnd = now()->parse($leave->to_date)->startOfDay();

            while ($cursor->lte($leaveEnd)) {
                $dateKey = $cursor->toDateString();
                if ($dateKey >= $start && $dateKey <= $end) {
                    $leaveByUserDate[$leave->user_id][$dateKey] = 'Leave';
                }
                $cursor->addDay();
            }
        }

        $grouped = $dayRows->groupBy('user_id');

        return [$days, $grouped, $leaveByUserDate];
    }

    public function monthlyAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $start = now()->setDate($validated['year'], $validated['month'], 1)->startOfMonth()->toDateString();
        $end = now()->setDate($validated['year'], $validated['month'], 1)->endOfMonth()->toDateString();

        [$days, $groupedRows, $leaveByUserDate] = $this->buildAttendanceStatusMap($start, $end);

        $rows = $groupedRows->map(function ($records, $userId) use ($days, $leaveByUserDate) {
            $first = $records->first();
            $recordsByDate = $records->filter(fn ($row) => !empty($row->date))->keyBy('date');

            $dayStatuses = $days->map(function ($date) use ($recordsByDate, $leaveByUserDate, $userId) {
                $record = $recordsByDate->get($date);
                $status = $record?->status;

                if (($leaveByUserDate[$userId][$date] ?? null) === 'Leave') {
                    $status = 'Leave';
                }

                return [
                    'date' => $date,
                    'status' => $status ?? 'Absent',
                    'late' => (bool) ($record?->late ?? false),
                    'overtime_minutes' => (int) ($record?->overtime_minutes ?? 0),
                    'code' => ($leaveByUserDate[$userId][$date] ?? null) === 'Leave'
                        ? 'L'
                        : match ($status ?? 'Absent') {
                            'Present' => (($record?->late ?? false) ? 'LT' : 'P'),
                            'Leave' => 'L',
                            default => 'A',
                        },
                ];
            })->values();

            return [
                'employee_code' => $first->employee_code,
                'name' => $first->name,
                'department' => $first->department,
                'designation' => $first->designation,
                'present_days' => $dayStatuses->where('status', 'Present')->count(),
                'absent_days' => $dayStatuses->where('status', 'Absent')->count(),
                'leave_days' => $dayStatuses->where('status', 'Leave')->count(),
                'late_days' => $dayStatuses->where('late', true)->count(),
                'overtime_minutes' => $dayStatuses->sum('overtime_minutes'),
                'days' => $dayStatuses,
            ];
        })->sortBy('employee_code')->values();

        return response()->json([
            'ok' => true,
            'range' => ['start' => $start, 'end' => $end],
            'dates' => $days->values(),
            'rows' => $rows,
        ]);
    }

    public function monthlyAttendanceCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $start = now()->setDate($validated['year'], $validated['month'], 1)->startOfMonth()->toDateString();
        $end = now()->setDate($validated['year'], $validated['month'], 1)->endOfMonth()->toDateString();

        $payload = $this->monthlyAttendance(new Request([
            'year' => $validated['year'],
            'month' => $validated['month'],
        ]))->getData(true);
        $dates = $payload['dates'] ?? [];
        $rows = $payload['rows'] ?? [];

        $filename = sprintf('attendance-monthly-%04d-%02d.csv', $validated['year'], $validated['month']);

        return response()->streamDownload(function () use ($rows, $dates) {
            $out = fopen('php://output', 'w');
            $headers = ['Employee ID', 'Name', 'Department', 'Designation'];
            foreach ($dates as $date) {
                $headers[] = $date;
            }
            $headers = array_merge($headers, ['Present', 'Absent', 'Leave', 'Late', 'Overtime (min)']);
            fputcsv($out, $headers);

            foreach ($rows as $r) {
                $row = [
                    $r['employee_code'] ?? '',
                    $r['name'] ?? '',
                    $r['department'] ?? '',
                    $r['designation'] ?? '',
                ];
                $dayMap = collect($r['days'] ?? [])->keyBy('date');
                foreach ($dates as $date) {
                    $row[] = $dayMap->get($date)['code'] ?? 'A';
                }
                $row = array_merge($row, [
                    (int) ($r['present_days'] ?? 0),
                    (int) ($r['absent_days'] ?? 0),
                    (int) ($r['leave_days'] ?? 0),
                    (int) ($r['late_days'] ?? 0),
                    (int) ($r['overtime_minutes'] ?? 0),
                ]);
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $canSeeConfidential = $request->user()->role === 'admin';

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
            'employee_profiles.status',
            'employee_profiles.employment_type',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'shifts.name as shift_name',
            'shifts.start_time as shift_start',
            'shifts.end_time as shift_end',
        ];

        if ($canSeeConfidential) {
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

        $rows = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->select($select)
            ->orderBy('users.employee_code')
            ->get();

        return response()->json(['ok' => true, 'employees' => $rows]);
    }

    public function employeesCsv(Request $request): StreamedResponse
    {
        $canSeeConfidential = $request->user()->role === 'admin';

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
            'employee_profiles.status',
            'employee_profiles.employment_type',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
            'shifts.name as shift_name',
            'shifts.start_time as shift_start',
            'shifts.end_time as shift_end',
        ];

        if ($canSeeConfidential) {
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

        $rows = DB::table('users')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->select($select)
            ->orderBy('users.employee_code')
            ->cursor();

        $filename = 'employees.csv';

        return response()->streamDownload(function () use ($rows, $canSeeConfidential) {
            $out = fopen('php://output', 'w');

            $headers = ['Employee ID', 'Name', 'Email', 'Role', 'Department', 'Designation', 'Shift', 'Shift Start', 'Shift End', 'DOJ', 'Probation End', 'Last Working Date', 'Status', 'Type', 'Phone', 'Personal Email'];
            if ($canSeeConfidential) {
                $headers = array_merge($headers, ['Basic', 'House', 'Transport', 'Tax', 'Bank', 'Account', 'IBAN']);
            }
            fputcsv($out, $headers);

            foreach ($rows as $r) {
                $row = [
                    $r->employee_code,
                    $r->name,
                    $r->email,
                    $r->role,
                    $r->department ?? '',
                    $r->designation ?? '',
                    $r->shift_name ?? '',
                    $r->shift_start ? substr((string) $r->shift_start, 0, 5) : '',
                    $r->shift_end ? substr((string) $r->shift_end, 0, 5) : '',
                    $r->date_of_joining ?? '',
                    $r->probation_end_date ?? '',
                    $r->last_working_date ?? '',
                    $r->status ?? '',
                    $r->employment_type ?? '',
                    $r->personal_phone ?? '',
                    $r->personal_email ?? '',
                ];
                if ($canSeeConfidential) {
                    $row = array_merge($row, [
                        $r->basic_salary ?? '',
                        $r->house_allowance ?? '',
                        $r->transport_allowance ?? '',
                        $r->tax_deduction ?? '',
                        $r->bank_name ?? '',
                        $r->bank_account_no ?? '',
                        $r->bank_iban ?? '',
                    ]);
                }
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
