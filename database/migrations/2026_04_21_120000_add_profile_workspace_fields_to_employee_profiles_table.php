<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('work_location')->nullable()->after('status');
            $table->date('confirmation_date')->nullable()->after('work_location');
            $table->string('marital_status', 30)->nullable()->after('address');
            $table->string('passport_no', 50)->nullable()->after('cnic');
            $table->string('pay_period', 50)->nullable()->after('transport_allowance');
            $table->date('salary_start_date')->nullable()->after('pay_period');
            $table->unsignedInteger('contribution_amount')->nullable()->after('salary_start_date');
            $table->unsignedInteger('other_deductions')->nullable()->after('contribution_amount');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'work_location',
                'confirmation_date',
                'marital_status',
                'passport_no',
                'pay_period',
                'salary_start_date',
                'contribution_amount',
                'other_deductions',
            ]);
        });
    }
};
