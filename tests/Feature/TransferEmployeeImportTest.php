<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferEmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_employee_csv_and_create_missing_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-700',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            implode("\n", [
                'Employee Code,Full Name,Official Email,Personal Email,Team,Job Title,Date of Joining,T-Shirt Size,Laptop Serial',
                'IMP-001,Amina Shah,amina.shah@example.test,amina.personal@example.test,Engineering,QA Analyst,2026-05-01,Medium,LT-9911',
            ])
        );

        $response = $this
            ->actingAs($admin)
            ->post('/api/transfer/employees/import', [
                'file' => $file,
            ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'imported' => 1,
            'skipped' => 0,
        ]);
        $response->assertJsonFragment(['created_fields' => ['T-Shirt Size', 'Laptop Serial']]);

        $employee = User::where('employee_code', 'IMP-001')->first();
        $this->assertNotNull($employee);
        $this->assertSame('Amina Shah', $employee->name);

        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'designation' => 'QA Analyst',
            'date_of_joining' => '2026-05-01',
            'personal_email' => 'amina.personal@example.test',
        ]);

        $customValues = DB::table('employee_custom_field_values')
            ->join('employee_custom_fields', 'employee_custom_fields.id', '=', 'employee_custom_field_values.field_id')
            ->where('employee_custom_field_values.user_id', $employee->id)
            ->pluck('employee_custom_field_values.value', 'employee_custom_fields.label')
            ->all();

        $this->assertSame('Medium', $customValues['T-Shirt Size'] ?? null);
        $this->assertSame('LT-9911', $customValues['Laptop Serial'] ?? null);
    }

    public function test_admin_can_import_semicolon_delimited_employee_csv(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-701',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            implode("\n", [
                'Employee Code;Full Name;Official Email;Personal Email;Team;Job Title;Date of Joining',
                'IMP-002;Bilal Khan;bilal.khan@example.test;bilal.personal@example.test;Operations;Coordinator;2026-05-02',
            ])
        );

        $response = $this
            ->actingAs($admin)
            ->post('/api/transfer/employees/import', [
                'file' => $file,
            ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'imported' => 1,
        ]);

        $employee = User::where('employee_code', 'IMP-002')->first();
        $this->assertNotNull($employee);
        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'designation' => 'Coordinator',
            'date_of_joining' => '2026-05-02',
        ]);
    }

    public function test_admin_can_import_slash_formatted_dates(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-702',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            implode("\n", [
                'Employee Code,Full Name,Official Email,Personal Email,Team,Job Title,Date of Joining,Date of Birth,Gender',
                'IMP-003,Hina Malik,hina.malik@example.test,hina.personal@example.test,HR,People Partner,04/05/2026,17/12/1998,Female',
            ])
        );

        $response = $this
            ->actingAs($admin)
            ->post('/api/transfer/employees/import', [
                'file' => $file,
            ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'imported' => 1,
        ]);

        $employee = User::where('employee_code', 'IMP-003')->first();
        $this->assertNotNull($employee);
        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $employee->id,
            'date_of_joining' => '2026-05-04',
            'date_of_birth' => '1998-12-17',
            'gender' => 'Female',
        ]);
    }
}
