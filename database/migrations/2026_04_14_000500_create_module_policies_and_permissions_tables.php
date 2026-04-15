<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_policies', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50); // users, attendance, leave, announcements, reports, company
            $table->string('key', 100);
            $table->string('value')->nullable();
            $table->string('value_type', 20)->default('string'); // string/int/bool/json
            $table->timestamps();

            $table->unique(['module', 'key']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // e.g. attendance.punch, employees.view_confidential
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20); // admin/hr/employee
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
            $table->index(['role', 'allowed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('module_policies');
    }
};

