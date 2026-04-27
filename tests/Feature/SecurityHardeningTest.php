<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_bootstrap_redacts_other_employee_sensitive_fields(): void
    {
        $viewer = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'name' => 'Viewer User',
        ]);

        $other = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-101',
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);

        $deptId = DB::table('departments')->insertGetId([
            'name' => 'Engineering',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            [
                'user_id' => $viewer->id,
                'department_id' => $deptId,
                'designation' => 'Developer',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'personal_phone' => '03000000000',
                'cnic_document_path' => null,
                'cnic_document_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $other->id,
                'department_id' => $deptId,
                'designation' => 'QA Engineer',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'personal_phone' => '03112223333',
                'cnic_document_path' => 'employee-documents/cnic-test.pdf',
                'cnic_document_name' => 'cnic-test.pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson('/api/bootstrap');

        $response->assertOk()->assertJson(['ok' => true]);

        $employee = collect($response->json('employees'))
            ->firstWhere('id', 'EMP-101');

        $this->assertNotNull($employee);
        $this->assertNull($employee['phone']);
        $this->assertNull($employee['email']);
        $this->assertNull($employee['cnicDocumentPath']);
        $this->assertNull($employee['cnicDocumentName']);
        $this->assertNull($employee['cnicDocumentUrl']);
    }

    public function test_employee_document_download_requires_authorized_viewer(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-100',
            'name' => 'Ali Raza',
        ]);

        $otherDeptUser = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-200',
            'name' => 'Sara Khan',
        ]);

        $engId = DB::table('departments')->insertGetId([
            'name' => 'Engineering',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $hrId = DB::table('departments')->insertGetId([
            'name' => 'HR',
            'color' => '#0D7373',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            [
                'user_id' => $employee->id,
                'department_id' => $engId,
                'designation' => 'Developer',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'cnic_document_path' => 'employee-documents/cnic-emp-100.pdf',
                'cnic_document_name' => 'cnic.pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $otherDeptUser->id,
                'department_id' => $hrId,
                'designation' => 'HR Executive',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'cnic_document_path' => null,
                'cnic_document_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $privateDir = storage_path('app/private/employee-documents');
        File::ensureDirectoryExists($privateDir);
        File::put($privateDir.'/cnic-emp-100.pdf', 'sensitive-doc');

        $ownerResponse = $this
            ->actingAs($employee)
            ->get('/api/employees/EMP-100/cnic-document');

        $ownerResponse->assertOk();

        $otherResponse = $this
            ->actingAs($otherDeptUser)
            ->get('/api/employees/EMP-100/cnic-document');

        $otherResponse->assertForbidden();
    }

    public function test_policy_index_exposes_authenticated_download_route_not_public_upload_path(): void
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
            'file_path' => 'company-policies/leave-policy.pdf',
            'file_name' => 'leave-policy.pdf',
            'file_size' => 4096,
            'uploaded_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($employee)
            ->getJson('/api/policies');

        $response->assertOk();
        $this->assertStringContainsString('/api/policies/', $response->json('policies.0.fileUrl'));
        $this->assertStringNotContainsString('/uploads/', $response->json('policies.0.fileUrl'));
    }
}
