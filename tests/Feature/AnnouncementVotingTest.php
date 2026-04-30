<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnnouncementVotingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_voting_announcement_and_employee_can_update_vote_until_closed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
            'name' => 'Admin User',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'name' => 'Employee User',
        ]);

        $createResponse = $this
            ->actingAs($admin)
            ->postJson('/api/announcements', [
                'title' => 'Company Dinner',
                'category' => 'Event',
                'audience' => 'all',
                'message' => 'Please confirm dinner attendance.',
                'has_vote' => true,
                'vote_question' => 'Will you attend?',
                'vote_options' => [
                    ['label' => 'Attend'],
                    ['label' => 'Not Attend'],
                ],
                'show_results_to_employees_after_close' => true,
            ]);

        $createResponse->assertCreated()->assertJson(['ok' => true]);
        $announcementId = (int) $createResponse->json('id');

        $options = DB::table('announcement_vote_options')
            ->where('announcement_id', $announcementId)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $options);

        $this
            ->actingAs($employee)
            ->postJson('/api/announcements/'.$announcementId.'/vote', [
                'option_id' => $options[0]->id,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('announcement_votes', [
            'announcement_id' => $announcementId,
            'user_id' => $employee->id,
            'option_id' => $options[0]->id,
        ]);

        $this
            ->actingAs($employee)
            ->postJson('/api/announcements/'.$announcementId.'/vote', [
                'option_id' => $options[1]->id,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('announcement_votes', [
            'announcement_id' => $announcementId,
            'user_id' => $employee->id,
            'option_id' => $options[1]->id,
        ]);

        $this
            ->actingAs($admin)
            ->patchJson('/api/announcements/'.$announcementId.'/vote/close', [])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this
            ->actingAs($employee)
            ->postJson('/api/announcements/'.$announcementId.'/vote', [
                'option_id' => $options[0]->id,
            ])
            ->assertStatus(422);
    }

    public function test_voting_requires_two_choices_and_results_are_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
        ]);

        $this
            ->actingAs($admin)
            ->postJson('/api/announcements', [
                'title' => 'Invalid Vote',
                'category' => 'Event',
                'audience' => 'all',
                'message' => 'Missing choices.',
                'has_vote' => true,
                'vote_question' => 'Will you attend?',
                'vote_options' => [
                    ['label' => 'Attend'],
                ],
            ])
            ->assertUnprocessable();

        $announcementId = DB::table('announcements')->insertGetId([
            'title' => 'Dinner Vote',
            'category' => 'Event',
            'message' => 'Please vote.',
            'audience' => 'all',
            'author_user_id' => $admin->id,
            'has_vote' => true,
            'vote_question' => 'Will you attend?',
            'vote_status' => 'open',
            'show_results_to_employees_after_close' => false,
            'published_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('announcement_vote_options')->insert([
            [
                'announcement_id' => $announcementId,
                'label' => 'Attend',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'announcement_id' => $announcementId,
                'label' => 'Not Attend',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this
            ->actingAs($employee)
            ->getJson('/api/announcements/'.$announcementId.'/vote/results')
            ->assertForbidden();

        $this
            ->actingAs($admin)
            ->getJson('/api/announcements/'.$announcementId.'/vote/results')
            ->assertOk()
            ->assertJsonPath('results.responses.0.employeeCode', 'ADM-100')
            ->assertJsonPath('results.responses.0.selectedOption', 'No Response');
    }
}
