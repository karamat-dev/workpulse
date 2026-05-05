<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_required_user_can_only_bootstrap_and_update_account(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-LOCK',
            'password' => Hash::make('OldPassword123!'),
            'password_must_change' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/bootstrap')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'passwordChangeRequired' => true,
                'currentUser' => [
                    'mustChangePassword' => true,
                ],
            ]);

        $this->actingAs($user)
            ->getJson('/api/me/profile')
            ->assertStatus(423)
            ->assertJson([
                'ok' => false,
                'passwordChangeRequired' => true,
            ]);
    }

    public function test_setting_new_password_clears_required_password_change_gate(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-SET',
            'password' => Hash::make('OldPassword123!'),
            'password_must_change' => true,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/me/account', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'passwordChangeRequired' => false,
            ]);

        $this->assertFalse((bool) $user->refresh()->password_must_change);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));

        $this->actingAs($user)
            ->getJson('/api/me/profile')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_password_change_required_user_cannot_continue_without_new_password(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-KEEP',
            'password' => Hash::make('OldPassword123!'),
            'password_must_change' => true,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/me/account', [
                'current_password' => 'OldPassword123!',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'ok' => false,
                'passwordChangeRequired' => true,
            ]);

        $this->assertTrue((bool) $user->refresh()->password_must_change);
    }

    public function test_required_password_change_must_use_a_different_password(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-DIFF',
            'password' => Hash::make('SamePassword123!'),
            'password_must_change' => true,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/me/account', [
                'current_password' => 'SamePassword123!',
                'password' => 'SamePassword123!',
                'password_confirmation' => 'SamePassword123!',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertTrue((bool) $user->refresh()->password_must_change);
    }
}
