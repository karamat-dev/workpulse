<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20)->default('Absent'); // Present/Absent/Leave/Holiday/Weekend
            $table->boolean('late')->default(false);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20); // clock_in/clock_out/break_out/break_in
            $table->dateTime('punched_at');
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['type', 'punched_at']);
        });

        Schema::create('attendance_regulation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 50);  // Missing Clock In, Wrong Clock Out Time, Break Adjustment
            $table->string('original_value')->nullable();
            $table->string('requested_value')->nullable();
            $table->text('reason')->nullable();

            $table->string('status', 20)->default('Pending'); // Pending/Approved/Rejected
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_regulation_requests');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('attendance_days');
    }
};

