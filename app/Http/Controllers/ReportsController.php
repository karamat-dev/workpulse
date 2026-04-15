<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function monthlyAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $start = now()->setDate($validated['year'], $validated['month'], 1)->startOfMonth()->toDateString();
        $end = now()->setDate($validated['year'], $validated['month'], 1)->endOfMonth()->toDateString();

        $rows = DB::table('attendance_days')
            ->join('users', 'users.id', '=', 'attendance_days.user_id')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->whereBetween('attendance_days.date', [$start, $end])
            ->select([
                'users.employee_code',
                'users.name',
                'departments.name as department',
                DB::raw("SUM(attendance_days.status = 'Present') as present_days"),
                DB::raw("SUM(attendance_days.status = 'Absent') as absent_days"),
                DB::raw("SUM(attendance_days.status = 'Leave') as leave_days"),
                DB::raw('SUM(attendance_days.late = 1) as late_days'),
                DB::raw('SUM(attendance_days.overtime_minutes) as overtime_minutes'),
            ])
            ->groupBy('users.employee_code', 'users.name', 'departments.name')
            ->orderBy('users.employee_code')
            ->get();

        return response()->json([
            'ok' => true,
            'range' => ['start' => $start, 'end' => $end],
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

        $rows = DB::table('attendance_days')
            ->join('users', 'users.id', '=', 'attendance_days.user_id')
            ->leftJoin('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('departments', 'departments.id', '=', 'employee_profiles.department_id')
            ->whereBetween('attendance_days.date', [$start, $end])
            ->select([
                'users.employee_code',
                'users.name',
                'departments.name as department',
                DB::raw("SUM(attendance_days.status = 'Present') as present_days"),
                DB::raw("SUM(attendance_days.status = 'Absent') as absent_days"),
                DB::raw("SUM(attendance_days.status = 'Leave') as leave_days"),
                DB::raw('SUM(attendance_days.late = 1) as late_days'),
                DB::raw('SUM(attendance_days.overtime_minutes) as overtime_minutes'),
            ])
            ->groupBy('users.employee_code', 'users.name', 'departments.name')
            ->orderBy('users.employee_code')
            ->cursor();

        $filename = sprintf('attendance-monthly-%04d-%02d.csv', $validated['year'], $validated['month']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee ID', 'Name', 'Department', 'Present', 'Absent', 'Leave', 'Late', 'Overtime (min)']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->employee_code,
                    $r->name,
                    $r->department ?? '',
                    (int) $r->present_days,
                    (int) $r->absent_days,
                    (int) $r->leave_days,
                    (int) $r->late_days,
                    (int) $r->overtime_minutes,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $canSeeConfidential = $request->user()->hasPermission('employees.view_confidential');

        $select = [
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as department',
            'employee_profiles.designation',
            'employee_profiles.date_of_joining',
            'employee_profiles.probation_end_date',
            'employee_profiles.status',
            'employee_profiles.employment_type',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
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
            ->select($select)
            ->orderBy('users.employee_code')
            ->get();

        return response()->json(['ok' => true, 'employees' => $rows]);
    }

    public function employeesCsv(Request $request): StreamedResponse
    {
        $canSeeConfidential = $request->user()->hasPermission('employees.view_confidential');

        $select = [
            'users.employee_code',
            'users.name',
            'users.email',
            'users.role',
            'departments.name as department',
            'employee_profiles.designation',
            'employee_profiles.date_of_joining',
            'employee_profiles.probation_end_date',
            'employee_profiles.status',
            'employee_profiles.employment_type',
            'employee_profiles.personal_phone',
            'employee_profiles.personal_email',
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
            ->select($select)
            ->orderBy('users.employee_code')
            ->cursor();

        $filename = 'employees.csv';

        return response()->streamDownload(function () use ($rows, $canSeeConfidential) {
            $out = fopen('php://output', 'w');

            $headers = ['Employee ID', 'Name', 'Email', 'Role', 'Department', 'Designation', 'DOJ', 'Probation End', 'Status', 'Type', 'Phone', 'Personal Email'];
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
                    $r->date_of_joining ?? '',
                    $r->probation_end_date ?? '',
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

