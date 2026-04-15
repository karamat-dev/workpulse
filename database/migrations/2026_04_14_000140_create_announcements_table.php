<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 50)->nullable();
            $table->text('message');
            $table->string('audience', 50)->default('all'); // all, department:<id>, role:<role>
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('published_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};

