<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferCompanyDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_company_details_from_json(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-500',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'company-details.json',
            json_encode([
                'company' => [
                    'company_name' => 'Acme Labs',
                    'website_link' => 'https://acme.test',
                    'official_email' => 'hello@acme.test',
                    'official_contact_no' => '+1 555 0100',
                    'office_location' => 'New York',
                    'linkedin_page' => 'linkedin.com/company/acme',
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = $this
            ->actingAs($admin)
            ->post('/api/transfer/company/import', [
                'file' => $file,
            ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'message' => 'Company details imported successfully.',
        ]);

        $this->assertDatabaseHas('company_settings', [
            'id' => 1,
            'company_name' => 'Acme Labs',
            'official_email' => 'hello@acme.test',
            'office_location' => 'New York',
        ]);
    }

    public function test_admin_can_export_company_details_as_json(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADM-501',
        ]);

        DB::table('company_settings')->insert([
            'id' => 1,
            'company_name' => 'Acme Labs',
            'website_link' => 'https://acme.test',
            'official_email' => 'hello@acme.test',
            'official_contact_no' => '+1 555 0100',
            'office_location' => 'New York',
            'linkedin_page' => 'linkedin.com/company/acme',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/api/transfer/company/export');

        $response->assertOk();
        $response->assertStreamed();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=workpulse-company-details.json');
        $this->assertStringContainsString('"company_name": "Acme Labs"', $response->streamedContent());
        $this->assertStringContainsString('"official_email": "hello@acme.test"', $response->streamedContent());
    }
}
