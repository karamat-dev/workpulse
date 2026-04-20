<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('cnic_document_path')->nullable()->after('cnic');
            $table->string('cnic_document_name')->nullable()->after('cnic_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['cnic_document_path', 'cnic_document_name']);
        });
    }
};
