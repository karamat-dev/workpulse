<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'employee')
            ->where('password_must_change', false)
            ->update([
                'password_must_change' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'employee')
            ->where('password_must_change', true)
            ->update([
                'password_must_change' => false,
                'updated_at' => now(),
            ]);
    }
};
