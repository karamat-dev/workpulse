<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaveAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_leave_creates_attendance_leave_day_and_rejected_leave_removes_it(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-200',
        ]);

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
}
