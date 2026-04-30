<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('has_vote')->default(false)->after('audience');
            $table->string('vote_question')->nullable()->after('has_vote');
            $table->string('vote_status', 20)->default('open')->after('vote_question');
            $table->boolean('show_results_to_employees_after_close')->default(false)->after('vote_status');
        });

        Schema::create('announcement_vote_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('announcement_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('announcement_vote_options')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_votes');
        Schema::dropIfExists('announcement_vote_options');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'has_vote',
                'vote_question',
                'vote_status',
                'show_results_to_employees_after_close',
            ]);
        });
    }
};
