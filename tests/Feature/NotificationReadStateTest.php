<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_single_notification_read_and_unread(): void
    {
        $user = User::factory()->create();

        $notificationId = DB::table('employee_notifications')->insertGetId([
            'user_id' => $user->id,
            'type' => 'admin_custom_notification',
            'title' => 'Policy update',
            'message' => 'Please review the update.',
            'reference_type' => 'admin_custom_notification',
            'reference_code' => 'NTF-TEST',
            'meta' => null,
            'is_read' => false,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->patchJson("/api/me/notifications/{$notificationId}/read-state", [
                'is_read' => true,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'isRead' => true,
            ]);

        $this->assertDatabaseHas('employee_notifications', [
            'id' => $notificationId,
            'is_read' => true,
        ]);

        $this
            ->actingAs($user)
            ->patchJson("/api/me/notifications/{$notificationId}/read-state", [
                'is_read' => false,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'isRead' => false,
            ]);

        $this->assertDatabaseHas('employee_notifications', [
            'id' => $notificationId,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function test_user_cannot_update_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $notificationId = DB::table('employee_notifications')->insertGetId([
            'user_id' => $owner->id,
            'type' => 'admin_custom_notification',
            'title' => 'Private update',
            'message' => 'Owner only.',
            'reference_type' => 'admin_custom_notification',
            'reference_code' => 'NTF-PRIVATE',
            'meta' => null,
            'is_read' => false,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->actingAs($other)
            ->patchJson("/api/me/notifications/{$notificationId}/read-state", [
                'is_read' => true,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('employee_notifications', [
            'id' => $notificationId,
            'is_read' => false,
        ]);
    }
}
