<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createEmployeeProfile(int $userId, string $employmentType = 'Permanent'): void
    {
        DB::table('employee_profiles')->insert([
            'user_id' => $userId,
            'designation' => 'QA Engineer',
            'date_of_joining' => now()->subMonth()->toDateString(),
            'employment_type' => $employmentType,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grantEmployeeLeaveApplyPermission(): void
    {
        $permissionId = DB::table('permissions')->insertGetId([
            'key' => 'leave.apply',
            'label' => 'Apply for leave',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            'role' => 'employee',
            'permission_id' => $permissionId,
            'allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_approved_leave_creates_attendance_leave_day_and_rejected_leave_removes_it(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-200',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

        DB::table('leave_types')->insert([
            'name' => 'Unpaid Leave',
            'code' => 'unpaid',
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $date = now()->addDay()->toDateString();

        $apply = $this
            ->actingAs($admin)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'unpaid',
                'from_date' => $date,
                'to_date' => $date,
                'days' => 1,
            ]);

        $apply->assertCreated()->assertJson(['ok' => true]);

        $code = $apply->json('code');

        $approve = $this
            ->actingAs($admin)
            ->patchJson("/api/leave/{$code}/review", [
                'step' => 'hr',
                'status' => 'Approved',
            ]);

        $approve->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $date,
            'status' => 'Leave',
            'late' => 0,
            'overtime_minutes' => 0,
            'worked_minutes' => 0,
        ]);

        $reject = $this
            ->actingAs($admin)
            ->patchJson("/api/leave/{$code}/review", [
                'step' => 'hr',
                'status' => 'Rejected',
            ]);

        $reject->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('attendance_days', [
            'user_id' => $admin->id,
            'date' => $date,
        ]);
    }

    public function test_user_cannot_punch_on_an_approved_leave_day(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-201',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

        DB::table('leave_types')->insert([
            'name' => 'Unpaid Leave',
            'code' => 'unpaid',
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $date = now()->addDay()->toDateString();

        $apply = $this
            ->actingAs($admin)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'unpaid',
                'from_date' => $date,
                'to_date' => $date,
                'days' => 1,
            ]);

        $apply->assertCreated()->assertJson(['ok' => true]);
        $code = $apply->json('code');

        $this
            ->actingAs($admin)
            ->patchJson("/api/leave/{$code}/review", [
                'step' => 'hr',
                'status' => 'Approved',
            ])
            ->assertOk();

        $punch = $this
            ->actingAs($admin)
            ->postJson('/api/attendance/punch', [
                'type' => 'clock_in',
                'punched_at' => $date.' 09:00:00',
            ]);

        $punch->assertStatus(422)->assertJson([
            'ok' => false,
            'message' => 'You cannot punch attendance on an approved leave day.',
        ]);

        $this->assertDatabaseMissing('attendance_punches', [
            'user_id' => $admin->id,
            'date' => $date,
            'type' => 'clock_in',
        ]);
    }

    public function test_intern_employee_cannot_apply_for_leave(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-300',
        ]);
        $this->createEmployeeProfile($employee->id, 'Intern');

        DB::table('leave_types')->insert([
            'name' => 'Unpaid Leave',
            'code' => 'unpaid',
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $date = now()->addDay()->toDateString();

        $response = $this
            ->actingAs($employee)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'unpaid',
                'from_date' => $date,
                'to_date' => $date,
            ]);

        $response->assertStatus(422)->assertJson([
            'ok' => false,
            'message' => 'Only permanent employees can apply for leave.',
        ]);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_permanent_employee_can_apply_for_leave(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-301',
        ]);
        $this->createEmployeeProfile($employee->id, 'Permanent');

        DB::table('leave_types')->insert([
            'name' => 'Unpaid Leave',
            'code' => 'unpaid',
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $date = now()->addDay()->toDateString();

        $response = $this
            ->actingAs($employee)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'unpaid',
                'from_date' => $date,
                'to_date' => $date,
            ]);

        $response->assertCreated()->assertJson([
            'ok' => true,
        ]);

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_pending_leave_requests_do_not_reduce_available_quota(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-302',
        ]);
        $this->createEmployeeProfile($employee->id, 'Permanent');

        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'name' => 'Annual Leave',
            'code' => 'annual',
            'paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leave_policies')->insert([
            'year' => (int) now()->format('Y'),
            'leave_type_id' => $leaveTypeId,
            'quota_days' => 5,
            'carry_forward_days' => 0,
            'pro_rata' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leave_requests')->insert([
            'code' => 'LV-RESERVED1',
            'user_id' => $employee->id,
            'leave_type_id' => $leaveTypeId,
            'from_date' => now()->addDays(2)->toDateString(),
            'to_date' => now()->addDays(4)->toDateString(),
            'duration_type' => 'full_day',
            'half_day_slot' => null,
            'days' => 3,
            'reason' => 'Already pending approval',
            'handover_to' => null,
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('leave_approvals')->insert([
            [
                'leave_request_id' => DB::table('leave_requests')->where('code', 'LV-RESERVED1')->value('id'),
                'step' => 'manager',
                'reviewer_user_id' => null,
                'status' => 'Pending',
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'leave_request_id' => DB::table('leave_requests')->where('code', 'LV-RESERVED1')->value('id'),
                'step' => 'hr',
                'reviewer_user_id' => null,
                'status' => 'Waiting',
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($employee)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'annual',
                'from_date' => now()->addDays(8)->toDateString(),
                'to_date' => now()->addDays(10)->toDateString(),
                'reason' => 'Pending leave should not block this request',
            ]);

        $response->assertCreated()->assertJson([
            'ok' => true,
        ]);

        $this->assertDatabaseCount('leave_requests', 2);
    }
}
