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

    private function createProfileFor(User $user, string $department = 'Operations'): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'name' => $department,
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'designation' => 'Operations Lead',
            'date_of_joining' => '2026-01-10',
            'status' => 'Active',
            'employment_type' => 'Permanent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

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
                'personal_email' => 'areeba.personal@example.com',
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

    public function test_admin_can_create_admin_but_cannot_create_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-110',
        ]);

        $createAdmin = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Nadia',
                'lname' => 'Admin',
                'email' => 'nadia.admin@example.com',
                'personal_email' => 'nadia.personal@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Administrator',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'admin',
            ]);

        $createAdmin->assertCreated()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('users', [
            'email' => 'nadia.admin@example.com',
            'role' => 'admin',
        ]);

        $createSuperAdmin = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Zara',
                'lname' => 'Super',
                'email' => 'zara.super@example.com',
                'personal_email' => 'zara.personal@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Super Admin',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'manager',
            ]);

        $createSuperAdmin->assertForbidden()->assertJson([
            'ok' => false,
            'message' => 'Only a Super-Admin can create Super-Admin accounts.',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'zara.super@example.com',
        ]);
    }

    public function test_employee_official_and_personal_emails_must_be_different_and_unique(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-111',
        ]);

        $existing = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-111',
            'email' => 'existing.official@example.com',
        ]);
        $this->createProfileFor($existing, 'Operations');
        DB::table('employee_profiles')
            ->where('user_id', $existing->id)
            ->update(['personal_email' => 'existing.personal@example.com']);

        $sameEmails = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Same',
                'lname' => 'Email',
                'email' => 'same@example.com',
                'personal_email' => 'same@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Administrator',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'employee',
            ]);

        $sameEmails->assertStatus(422)->assertJson([
            'ok' => false,
            'message' => 'Official email and personal email must be different.',
        ]);

        $personalUsesOfficial = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Official',
                'lname' => 'Collision',
                'email' => 'new.official@example.com',
                'personal_email' => 'existing.official@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Administrator',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'employee',
            ]);

        $personalUsesOfficial->assertStatus(422)->assertJson([
            'ok' => false,
            'message' => 'Personal email is already used as another employee official email.',
        ]);

        $officialUsesPersonal = $this
            ->actingAs($admin)
            ->postJson('/api/employees', [
                'fname' => 'Personal',
                'lname' => 'Collision',
                'email' => 'existing.personal@example.com',
                'personal_email' => 'new.personal@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Administrator',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'employee',
            ]);

        $officialUsesPersonal->assertStatus(422)->assertJson([
            'ok' => false,
            'message' => 'Official email is already used as another employee personal email.',
        ]);
    }

    public function test_personal_email_cannot_be_used_for_login(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-112',
            'email' => 'login.official@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->createProfileFor($employee, 'Operations');
        DB::table('employee_profiles')
            ->where('user_id', $employee->id)
            ->update(['personal_email' => 'login.personal@example.com']);

        $this
            ->post('/login', [
                'email' => 'login.personal@example.com',
                'password' => 'password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this
            ->post('/login', [
                'email' => 'login.official@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($employee);
    }

    public function test_super_admin_can_create_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'manager',
            'employee_code' => 'SUP-100',
        ]);

        $response = $this
            ->actingAs($superAdmin)
            ->postJson('/api/employees', [
                'fname' => 'Zara',
                'lname' => 'Super',
                'email' => 'zara.super@example.com',
                'personal_email' => 'zara.personal@example.com',
                'password' => 'Secret123!',
                'dept' => 'Management',
                'desg' => 'Super Admin',
                'doj' => '2026-04-20',
                'type' => 'Permanent',
                'role' => 'manager',
            ]);

        $response->assertCreated()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('users', [
            'email' => 'zara.super@example.com',
            'role' => 'manager',
        ]);
    }

    public function test_admin_cannot_view_update_or_delete_super_admin_accounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-120',
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'manager',
            'employee_code' => 'SUP-120',
            'email' => 'super@example.com',
            'name' => 'Super Admin',
        ]);
        $this->createProfileFor($superAdmin, 'Management');

        $this
            ->actingAs($admin)
            ->getJson('/api/employees/SUP-120')
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'message' => 'Only a Super-Admin can view Super-Admin accounts.',
            ]);

        $this
            ->actingAs($admin)
            ->patchJson('/api/employees/SUP-120', [
                'fname' => 'Super',
                'lname' => 'Admin',
                'email' => 'super@example.com',
                'personal_email' => 'super.personal@example.com',
                'dept' => 'Management',
                'desg' => 'Super Admin',
                'doj' => '2026-01-10',
                'type' => 'Permanent',
                'role' => 'admin',
            ])
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'message' => 'Only a Super-Admin can change Super-Admin accounts.',
            ]);

        $this
            ->actingAs($admin)
            ->deleteJson('/api/employees/SUP-120')
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'message' => 'Only a Super-Admin can delete Super-Admin accounts.',
            ]);
    }

    public function test_admin_cannot_promote_employee_to_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-130',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-130',
            'email' => 'employee130@example.com',
            'name' => 'Ali Raza',
        ]);
        $this->createProfileFor($employee, 'Operations');

        $this
            ->actingAs($admin)
            ->patchJson('/api/employees/EMP-130', [
                'fname' => 'Ali',
                'lname' => 'Raza',
                'email' => 'employee130@example.com',
                'personal_email' => 'employee130.personal@example.com',
                'dept' => 'Operations',
                'desg' => 'Coordinator',
                'doj' => '2026-01-10',
                'type' => 'Permanent',
                'role' => 'manager',
            ])
            ->assertForbidden()
            ->assertJson([
                'ok' => false,
                'message' => 'Only a Super-Admin can create Super-Admin accounts.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'role' => 'employee',
        ]);
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
                'personal_email' => 'employee.personal@example.com',
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
                'personal_email' => 'employee.personal@example.com',
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
