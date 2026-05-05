<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    private const TEMPORARY_PASSWORD = 'TempEmployee123!@#';

    public function up(): void
    {
        DB::table('users')
            ->where('role', 'employee')
            ->orderBy('id')
            ->get(['id', 'password'])
            ->each(function (object $user): void {
                if (!Hash::check(self::TEMPORARY_PASSWORD, (string) $user->password)) {
                    return;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'password_must_change' => true,
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        //
    }
};
