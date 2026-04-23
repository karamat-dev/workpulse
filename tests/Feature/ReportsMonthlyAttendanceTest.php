<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsMonthlyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function createDepartment(string $name = 'Engineering'): int
    {
        return DB::table('departments')->insertGetId([
            'name' => $name,
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(string $employeeCode, string $name, int $departmentId): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
            'employee_code' => $employeeCode,
        ]);

        DB::table('employee_profiles')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'designation' => 'Software Engineer',
            'date_of_joining' => now()->subMonths(3)->toDateString(),
            'employment_type' => 'Permanent',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    public function test_monthly_attendance_supports_employee_filter_and_custom_range(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $departmentId = $this->createDepartment();
        $employeeA = $this->createEmployee('EMP-100', 'Alice Ahmed', $departmentId);
        $employeeB = $this->createEmployee('EMP-101', 'Bilal Khan', $departmentId);

        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'name' => 'Annual Leave',
            'code' => 'annual',
            'paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attendance_days')->insert([
            [
                'user_id' => $employeeA->id,
                'date' => '2026-04-01',
                'status' => 'Present',
                'late' => false,
                'overtime_minutes' => 45,
                'worked_minutes' => 480,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $employeeA->id,
                'date' => '2026-04-02',
                'status' => 'Present',
                'late' => true,
                'overtime_minutes' => 0,
                'worked_minutes' => 480,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $employeeB->id,
                'date' => '2026-04-01',
                'status' => 'Present',
                'late' => false,
                'overtime_minutes' => 0,
                'worked_minutes' => 480,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('leave_requests')->insert([
            'code' => 'LV-REPORT-1',
            'user_id' => $employeeA->id,
            'leave_type_id' => $leaveTypeId,
            'from_date' => '2026-04-03',
            'to_date' => '2026-04-03',
            'days' => 1,
            'status' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyResponse = $this
            ->actingAs($admin)
            ->getJson('/api/reports/attendance/monthly?year=2026&month=4&employee_code=EMP-100');

        $monthlyResponse
            ->assertOk()
            ->assertJsonPath('range.start', '2026-04-01')
            ->assertJsonPath('range.end', '2026-04-30')
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.employee_code', 'EMP-100')
            ->assertJsonPath('rows.0.present_days', 2)
            ->assertJsonPath('rows.0.leave_days', 1)
            ->assertJsonPath('rows.0.late_days', 1)
            ->assertJsonPath('rows.0.overtime_minutes', 45);

        $customRangeResponse = $this
            ->actingAs($admin)
            ->getJson('/api/reports/attendance/monthly?start_date=2026-04-02&end_date=2026-04-03&employee_code=EMP-100');

        $customRangeResponse
            ->assertOk()
            ->assertJsonPath('filter.mode', 'custom')
            ->assertJsonPath('range.start', '2026-04-02')
            ->assertJsonPath('range.end', '2026-04-03')
            ->assertJsonCount(2, 'dates')
            ->assertJsonPath('rows.0.present_days', 1)
            ->assertJsonPath('rows.0.leave_days', 1)
            ->assertJsonPath('rows.0.absent_days', 0);
    }

    public function test_monthly_attendance_csv_supports_custom_range_export(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-101',
        ]);

        $departmentId = $this->createDepartment('People');
        $employee = $this->createEmployee('EMP-200', 'Sara Noor', $departmentId);

        DB::table('attendance_days')->insert([
            'user_id' => $employee->id,
            'date' => '2026-04-10',
            'status' => 'Present',
            'late' => false,
            'overtime_minutes' => 30,
            'worked_minutes' => 480,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/api/reports/attendance/monthly.csv?start_date=2026-04-10&end_date=2026-04-10&employee_code=EMP-200');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=attendance-2026-04-10-to-2026-04-10.csv');
        $response->assertSee('Employee ID,Name,Department,Designation,2026-04-10,Present,Absent,Leave,Late,Overtime (min)', false);
        $response->assertSee('EMP-200,"Sara Noor",People,"Software Engineer",P,1,0,0,0,30', false);
    }
}
