<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // Annual Leave, Sick Leave...
            $table->string('code', 30)->unique();      // annual, sick, ...
            $table->boolean('paid')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->index();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('quota_days')->default(0); // annual basis
            $table->boolean('pro_rata')->default(true);
            $table->unsignedSmallInteger('carry_forward_days')->default(0);
            $table->timestamps();

            $table->unique(['year', 'leave_type_id']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->decimal('allocated_days', 6, 2)->default(0);
            $table->decimal('used_days', 6, 2)->default(0);
            $table->decimal('remaining_days', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year', 'leave_type_id']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days', 6, 2)->default(1);
            $table->text('reason')->nullable();
            $table->string('handover_to')->nullable();

            $table->string('status', 20)->default('Pending'); // Pending/Approved/Rejected/Cancelled
            $table->timestamps();

            $table->index(['user_id', 'from_date', 'to_date']);
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->string('step', 20); // manager/hr
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('Pending'); // Pending/Approved/Rejected/Waiting
            $table->text('notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['leave_request_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('leave_types');
    }
};

