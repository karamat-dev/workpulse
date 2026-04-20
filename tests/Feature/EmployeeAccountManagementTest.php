<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_employee_can_update_only_their_own_email_and_password_from_account_endpoint(): void
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
                'email' => 'new@example.com',
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $employee->refresh();

        $this->assertSame('new@example.com', $employee->email);
        $this->assertTrue(Hash::check('NewPassword123!', $employee->password));
    }
}
