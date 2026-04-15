<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Job / HR
            $table->string('designation')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('employment_type', 30)->nullable(); // Permanent / Probation / Contract
            $table->string('status', 30)->default('Active');   // Active / Probation / Inactive

            // Personal
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('cnic', 30)->nullable();
            $table->string('personal_phone', 40)->nullable();
            $table->string('personal_email')->nullable();
            $table->string('address')->nullable();
            $table->string('blood_group', 10)->nullable();

            // Next of kin
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone', 40)->nullable();

            // Salary / bank (confidential)
            $table->unsignedInteger('basic_salary')->nullable();
            $table->unsignedInteger('house_allowance')->nullable();
            $table->unsignedInteger('transport_allowance')->nullable();
            $table->unsignedInteger('tax_deduction')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_iban')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};

