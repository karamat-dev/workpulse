<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyPoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_delete_company_policy_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $uploadResponse = $this
            ->actingAs($admin)
            ->post('/api/policies', [
                'title' => 'Attendance Policy',
                'policy_file' => UploadedFile::fake()->create('attendance-policy.pdf', 120, 'application/pdf'),
            ]);

        $uploadResponse->assertCreated()->assertJson(['ok' => true]);

        $policyId = DB::table('company_policies')->value('id');

        $this->assertDatabaseHas('company_policies', [
            'id' => $policyId,
            'title' => 'Attendance Policy',
            'uploaded_by' => $admin->id,
        ]);
        $this->assertGreaterThan(0, (int) DB::table('company_policies')->where('id', $policyId)->value('file_size'));

        $deleteResponse = $this
            ->actingAs($admin)
            ->deleteJson('/api/policies/'.$policyId);

        $deleteResponse->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('company_policies', [
            'id' => $policyId,
        ]);
    }

    public function test_employee_can_view_but_cannot_manage_company_policies(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-100',
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
        ]);

        DB::table('company_policies')->insert([
            'title' => 'Leave Policy',
            'file_path' => 'uploads/company-policies/leave-policy.pdf',
            'file_name' => 'leave-policy.pdf',
            'file_size' => 4096,
            'uploaded_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $indexResponse = $this
            ->actingAs($employee)
            ->getJson('/api/policies');

        $indexResponse
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('policies.0.title', 'Leave Policy');

        $uploadResponse = $this
            ->actingAs($employee)
            ->post('/api/policies', [
                'title' => 'Unauthorized',
                'policy_file' => UploadedFile::fake()->create('unauthorized.pdf', 50, 'application/pdf'),
            ]);

        $uploadResponse->assertForbidden();
    }
}
