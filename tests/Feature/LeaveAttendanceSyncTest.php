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

    public function test_user_can_clock_in_on_an_approved_leave_day_and_gets_cancellation_reminder(): void
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

        $punch->assertOk()->assertJson([
            'ok' => true,
            'requires_leave_cancellation_regulation' => true,
        ]);

        $this->assertDatabaseHas('attendance_punches', [
            'user_id' => $admin->id,
            'date' => $date,
            'type' => 'clock_in',
        ]);

        $this->assertDatabaseHas('employee_notifications', [
            'user_id' => $admin->id,
            'type' => 'leave_cancellation_regulation_reminder',
            'reference_code' => $code,
        ]);
    }

    public function test_late_status_uses_configured_grace_period_when_employee_has_no_shift(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-204',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

        DB::table('module_policies')->updateOrInsert(
            ['module' => 'attendance', 'key' => 'shift_start'],
            ['value' => '11:00', 'value_type' => 'string', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('module_policies')->updateOrInsert(
            ['module' => 'attendance', 'key' => 'grace_minutes'],
            ['value' => '60', 'value_type' => 'int', 'created_at' => now(), 'updated_at' => now()]
        );

        $onTimeDate = now()->addDays(2)->toDateString();
        $lateDate = now()->addDays(3)->toDateString();

        $this
            ->actingAs($admin)
            ->postJson('/api/attendance/punch', [
                'type' => 'clock_in',
                'punched_at' => $onTimeDate.' 11:50:00',
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $onTimeDate,
            'status' => 'Present',
            'late' => 0,
        ]);

        $this
            ->actingAs($admin)
            ->postJson('/api/attendance/punch', [
                'type' => 'clock_in',
                'punched_at' => $lateDate.' 12:01:00',
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $lateDate,
            'status' => 'Present',
            'late' => 1,
        ]);
    }

    public function test_updating_shift_grace_recalculates_today_late_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-205',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

        $shiftId = DB::table('shifts')->insertGetId([
            'code' => 'day',
            'name' => 'Day Shift',
            'start_time' => '11:00:00',
            'end_time' => '20:00:00',
            'grace_minutes' => 10,
            'break_minutes' => 60,
            'working_days' => 'Mon-Fri',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')
            ->where('user_id', $admin->id)
            ->update(['shift_id' => $shiftId]);

        $today = now()->toDateString();

        $this
            ->actingAs($admin)
            ->postJson('/api/attendance/punch', [
                'type' => 'clock_in',
                'punched_at' => $today.' 11:50:00',
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $today,
            'late' => 1,
        ]);

        $this
            ->actingAs($admin)
            ->patchJson("/api/shifts/{$shiftId}", [
                'name' => 'Day Shift',
                'code' => 'day',
                'start' => '11:00',
                'end' => '20:00',
                'grace' => 60,
                'break' => 60,
                'workingDays' => 'Mon-Fri',
                'active' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $today,
            'late' => 0,
        ]);
    }

    public function test_approved_regulation_for_worked_leave_day_refunds_leave_balance(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-203',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

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

        $date = now()->addDay()->toDateString();

        $apply = $this
            ->actingAs($admin)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'annual',
                'from_date' => $date,
                'to_date' => $date,
                'days' => 1,
            ]);

        $apply->assertCreated()->assertJson(['ok' => true]);
        $leaveCode = $apply->json('code');

        $this
            ->actingAs($admin)
            ->patchJson("/api/leave/{$leaveCode}/review", [
                'step' => 'hr',
                'status' => 'Approved',
            ])
            ->assertOk();

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $admin->id,
            'leave_type_id' => $leaveTypeId,
            'used_days' => 1,
            'remaining_days' => 4,
        ]);

        $this
            ->actingAs($admin)
            ->postJson('/api/attendance/punch', [
                'type' => 'clock_in',
                'punched_at' => $date.' 09:00:00',
            ])
            ->assertOk();

        $regulation = $this
            ->actingAs($admin)
            ->postJson('/api/attendance/regulations', [
                'date' => $date,
                'type' => 'Leave Cancellation',
                'original_value' => 'Approved leave '.$leaveCode,
                'requested_value' => 'In '.$date.' 09:00',
                'reason' => 'Worked on approved leave day; please cancel the leave for this date.',
            ]);

        $regulation->assertCreated()->assertJson(['ok' => true]);
        $regulationCode = $regulation->json('code');

        $this
            ->actingAs($admin)
            ->patchJson("/api/attendance/regulations/{$regulationCode}/review", [
                'status' => 'Approved',
            ])
            ->assertOk();

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $admin->id,
            'leave_type_id' => $leaveTypeId,
            'used_days' => 0,
            'remaining_days' => 5,
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'code' => $leaveCode,
            'status' => 'Cancelled',
            'days' => 0,
        ]);

        $this->assertDatabaseHas('leave_request_cancellations', [
            'user_id' => $admin->id,
            'date' => $date,
            'days' => 1,
        ]);

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $date,
            'status' => 'Present',
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
            'message' => 'Only permanent and contract employees can apply for leave.',
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

    public function test_contract_employee_can_apply_for_leave(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-304',
        ]);
        $this->createEmployeeProfile($employee->id, 'Contract');

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

    public function test_employee_can_apply_for_multi_day_leave_with_per_day_duration_breakdown(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-303',
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
            'quota_days' => 10,
            'carry_forward_days' => 0,
            'pro_rata' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monday = now()->next('Monday')->toDateString();
        $tuesday = now()->next('Monday')->addDay()->toDateString();
        $wednesday = now()->next('Monday')->addDays(2)->toDateString();

        $response = $this
            ->actingAs($employee)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'annual',
                'from_date' => $monday,
                'to_date' => $wednesday,
                'daily_breakdown' => [
                    ['date' => $monday, 'duration_type' => 'half_day', 'half_day_slot' => 'first_half'],
                    ['date' => $tuesday, 'duration_type' => 'full_day', 'half_day_slot' => null],
                    ['date' => $wednesday, 'duration_type' => 'full_day', 'half_day_slot' => null],
                ],
                'reason' => 'Mixed-duration leave plan',
            ]);

        $response->assertCreated()->assertJson(['ok' => true]);

        $request = DB::table('leave_requests')->where('user_id', $employee->id)->first();

        $this->assertNotNull($request);
        $this->assertSame(2.5, (float) $request->days);
        $this->assertSame('full_day', $request->duration_type);

        $dailyBreakdown = json_decode($request->daily_breakdown, true);
        $this->assertCount(3, $dailyBreakdown);
        $this->assertSame('half_day', $dailyBreakdown[0]['duration_type']);
        $this->assertSame('first_half', $dailyBreakdown[0]['half_day_slot']);
        $this->assertSame('full_day', $dailyBreakdown[1]['duration_type']);
        $this->assertSame('full_day', $dailyBreakdown[2]['duration_type']);
    }

    public function test_approving_multi_day_leave_with_daily_breakdown_syncs_each_day_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-202',
        ]);
        $this->createEmployeeProfile($admin->id, 'Permanent');

        DB::table('leave_types')->insert([
            'name' => 'Unpaid Leave',
            'code' => 'unpaid',
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monday = now()->next('Monday')->toDateString();
        $tuesday = now()->next('Monday')->addDay()->toDateString();
        $wednesday = now()->next('Monday')->addDays(2)->toDateString();

        $apply = $this
            ->actingAs($admin)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'unpaid',
                'from_date' => $monday,
                'to_date' => $wednesday,
                'daily_breakdown' => [
                    ['date' => $monday, 'duration_type' => 'half_day', 'half_day_slot' => 'first_half'],
                    ['date' => $tuesday, 'duration_type' => 'full_day', 'half_day_slot' => null],
                    ['date' => $wednesday, 'duration_type' => 'full_day', 'half_day_slot' => null],
                ],
                'reason' => 'Mixed-duration leave plan',
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

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $monday,
            'status' => 'Half Leave (First Half)',
        ]);

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $tuesday,
            'status' => 'Leave',
        ]);

        $this->assertDatabaseHas('attendance_days', [
            'user_id' => $admin->id,
            'date' => $wednesday,
            'status' => 'Leave',
        ]);
    }

    public function test_leave_request_creates_only_hr_approval_step(): void
    {
        $this->grantEmployeeLeaveApplyPermission();

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-909',
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
            'quota_days' => 10,
            'carry_forward_days' => 0,
            'pro_rata' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($employee)
            ->postJson('/api/leave/apply', [
                'leave_type_code' => 'annual',
                'from_date' => now()->addDays(5)->toDateString(),
                'to_date' => now()->addDays(5)->toDateString(),
                'reason' => 'One day leave',
            ]);

        $response->assertCreated()->assertJson(['ok' => true]);

        $leaveRequestId = DB::table('leave_requests')->where('user_id', $employee->id)->value('id');

        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $leaveRequestId,
            'step' => 'hr',
            'status' => 'Pending',
        ]);

        $this->assertDatabaseMissing('leave_approvals', [
            'leave_request_id' => $leaveRequestId,
            'step' => 'manager',
        ]);
    }
}
