<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_recovery_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 60);
            $table->string('label');
            $table->json('payload');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamp('expires_at');
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_type', 'deleted_at']);
            $table->index(['expires_at', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_recovery_items');
    }
};
