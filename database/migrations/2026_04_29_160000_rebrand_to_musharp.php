<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@workpulse.com')
            ->update(['email' => DB::raw("REPLACE(email, '@workpulse.com', '@musharp.com')")]);

        DB::table('employee_profiles')
            ->where('personal_email', 'like', '%@workpulse.com')
            ->update(['personal_email' => DB::raw("REPLACE(personal_email, '@workpulse.com', '@musharp.com')")]);

        DB::table('company_settings')
            ->where('id', 1)
            ->update([
                'company_name' => 'muSharp',
                'website_link' => 'musharp.com',
                'official_email' => 'info@musharp.com',
                'linkedin_page' => 'linkedin.com/company/musharp',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@musharp.com')
            ->update(['email' => DB::raw("REPLACE(email, '@musharp.com', '@workpulse.com')")]);

        DB::table('employee_profiles')
            ->where('personal_email', 'like', '%@musharp.com')
            ->update(['personal_email' => DB::raw("REPLACE(personal_email, '@musharp.com', '@workpulse.com')")]);
    }
};
