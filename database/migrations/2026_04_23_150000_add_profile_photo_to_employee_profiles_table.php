<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('cnic_document_name');
            $table->string('profile_photo_name')->nullable()->after('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_path', 'profile_photo_name']);
        });
    }
};
