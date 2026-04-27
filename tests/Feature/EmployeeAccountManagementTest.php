<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_with_a_defined_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Areeba',
                'lname' => 'Khan',
                'email' => 'areeba@example.com',
                'password' => 'Secret123!',
                'phone' => '03001234567',
                'dept' => 'Engineering',
                'desg' => 'Intern Developer',
                'doj' => '2026-04-20',
                'dop' => '2026-07-20',
                'lwd' => null,
                'type' => 'Intern',
                'manager' => null,
            ]);

        $response->assertCreated()->assertJson(['ok' => true]);

        $employee = User::where('email', 'areeba@example.com')->first();

        $this->assertNotNull($employee);
        $this->assertTrue(Hash::check('Secret123!', $employee->password));
    }

    public function test_employee_can_update_only_their_own_password_from_account_endpoint(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'email' => 'old@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this
            ->actingAs($employee)
            ->patchJson('/api/me/account', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $employee->refresh();

        $this->assertSame('old@example.com', $employee->email);
        $this->assertTrue(Hash::check('NewPassword123!', $employee->password));
    }

    public function test_admin_delete_archives_employee_into_ex_employee_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'email' => 'employee@example.com',
        ]);

        DB::table('employee_profiles')->insert([
            'user_id' => $employee->id,
            'designation' => 'Support Executive',
            'date_of_joining' => '2026-01-10',
            'status' => 'Active',
            'employment_type' => 'Permanent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->deleteJson('/api/employees/EMP-100');

        $response->assertOk()->assertJson([
            'ok' => true,
            'message' => 'Employee moved to ex-employee records.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'employee_code' => 'EMP-100',
        ]);

        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'status' => 'Inactive',
        ]);

        $this->assertNotNull(DB::table('employee_profiles')->where('user_id', $employee->id)->value('last_working_date'));
    }

    public function test_future_last_working_date_moves_employee_to_offboarding_status(): void
    {
        $futureDate = now()->addDay()->toDateString();

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'email' => 'employee@example.com',
            'name' => 'Ali Raza',
        ]);

        DB::table('departments')->insert([
            'id' => 1,
            'name' => 'Operations',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            'user_id' => $employee->id,
            'department_id' => 1,
            'designation' => 'Coordinator',
            'date_of_joining' => '2026-01-10',
            'status' => 'Active',
            'employment_type' => 'Permanent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->patchJson('/api/employees/EMP-100', [
                'fname' => 'Ali',
                'lname' => 'Raza',
                'email' => 'employee@example.com',
                'dept' => 'Operations',
                'desg' => 'Coordinator',
                'doj' => '2026-01-10',
                'lwd' => $futureDate,
                'status' => 'Active',
                'type' => 'Permanent',
                'role' => 'employee',
            ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'last_working_date' => $futureDate,
            'status' => 'Offboarding',
        ]);
    }

    public function test_past_last_working_date_moves_employee_to_ex_employee_status(): void
    {
        $pastDate = now()->subDay()->toDateString();

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'email' => 'employee@example.com',
            'name' => 'Ali Raza',
        ]);

        DB::table('departments')->insert([
            'id' => 1,
            'name' => 'Operations',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            'user_id' => $employee->id,
            'department_id' => 1,
            'designation' => 'Coordinator',
            'date_of_joining' => '2026-01-10',
            'status' => 'Active',
            'employment_type' => 'Permanent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->patchJson('/api/employees/EMP-100', [
                'fname' => 'Ali',
                'lname' => 'Raza',
                'email' => 'employee@example.com',
                'dept' => 'Operations',
                'desg' => 'Coordinator',
                'doj' => '2026-01-10',
                'lwd' => $pastDate,
                'status' => 'Active',
                'type' => 'Permanent',
                'role' => 'employee',
            ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'last_working_date' => $pastDate,
            'status' => 'Inactive',
        ]);
    }
}
