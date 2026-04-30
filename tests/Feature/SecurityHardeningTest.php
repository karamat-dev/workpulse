<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
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

    public function test_null_role_user_bootstrap_does_not_receive_other_department_data(): void
    {
        $viewer = User::factory()->create([
            'role' => '',
            'employee_code' => 'EMP-500',
            'name' => 'Scoped Viewer',
        ]);

        $other = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-501',
            'name' => 'Other Employee',
        ]);

        $engId = DB::table('departments')->insertGetId([
            'name' => 'Engineering',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $salesId = DB::table('departments')->insertGetId([
            'name' => 'Sales',
            'color' => '#0D7373',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            [
                'user_id' => $viewer->id,
                'department_id' => $engId,
                'designation' => 'Developer',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $other->id,
                'department_id' => $salesId,
                'designation' => 'Sales Executive',
                'date_of_joining' => '2026-01-10',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('attendance_days')->insert([
            'user_id' => $other->id,
            'date' => now()->toDateString(),
            'status' => 'Present',
            'late' => false,
            'worked_minutes' => 480,
            'overtime_minutes' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($viewer)
            ->getJson('/api/bootstrap');

        $response->assertOk()->assertJson(['ok' => true]);

        $employeeIds = collect($response->json('employees'))->pluck('id')->all();
        $attendanceEmployeeIds = collect($response->json('attendance'))->pluck('empId')->all();

        $this->assertContains('EMP-500', $employeeIds);
        $this->assertNotContains('EMP-501', $employeeIds);
        $this->assertNotContains('EMP-501', $attendanceEmployeeIds);
    }

    public function test_hr_employee_record_is_not_globally_visible_to_other_employee(): void
    {
        $target = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-310',
            'name' => 'Target User',
        ]);

        $viewer = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP-311',
            'name' => 'Viewer User',
        ]);

        $hrDeptId = DB::table('departments')->insertGetId([
            'name' => 'HR',
            'color' => '#0D7373',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $opsDeptId = DB::table('departments')->insertGetId([
            'name' => 'Operations',
            'color' => '#2447D0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_profiles')->insert([
            [
                'user_id' => $target->id,
                'department_id' => $hrDeptId,
                'designation' => 'HR Executive',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'cnic_document_path' => 'employee-documents/hr-private.pdf',
                'cnic_document_name' => 'hr-private.pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $viewer->id,
                'department_id' => $opsDeptId,
                'designation' => 'Coordinator',
                'status' => 'Active',
                'employment_type' => 'Permanent',
                'cnic_document_path' => null,
                'cnic_document_name' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        File::ensureDirectoryExists(storage_path('app/private/employee-documents'));
        File::put(storage_path('app/private/employee-documents/hr-private.pdf'), 'hr-sensitive-doc');

        $response = $this
            ->actingAs($viewer)
            ->get('/api/employees/EMP-310/cnic-document');

        $response->assertForbidden();
    }

    public function test_registration_assigns_employee_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Joiner',
            'email' => 'new.joiner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'new.joiner@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('employee', $user->role);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_policy_download_rejects_path_traversal(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-700',
        ]);

        $policyId = DB::table('company_policies')->insertGetId([
            'title' => 'Unsafe Policy',
            'file_path' => '../../.env',
            'file_name' => 'unsafe.txt',
            'file_size' => 128,
            'uploaded_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/api/policies/'.$policyId.'/file');

        $response->assertStatus(400);
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
