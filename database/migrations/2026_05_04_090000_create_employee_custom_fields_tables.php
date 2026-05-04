<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('type', 30)->default('text');
            $table->timestamps();
        });

        Schema::create('employee_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('employee_custom_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_custom_field_values');
        Schema::dropIfExists('employee_custom_fields');
    }
};
