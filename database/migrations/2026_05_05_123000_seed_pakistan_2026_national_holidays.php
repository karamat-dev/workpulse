<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $holidays = [
            ['date' => '2026-02-05', 'name' => 'Kashmir Day', 'type' => 'National'],
            ['date' => '2026-03-21', 'name' => 'Eid-ul-Fitr', 'type' => 'National'],
            ['date' => '2026-03-22', 'name' => 'Eid-ul-Fitr (Day 2)', 'type' => 'National'],
            ['date' => '2026-03-23', 'name' => 'Eid-ul-Fitr (Day 3) / Pakistan Day', 'type' => 'National'],
            ['date' => '2026-05-01', 'name' => 'Labour Day', 'type' => 'National'],
            ['date' => '2026-05-27', 'name' => 'Eid-ul-Azha', 'type' => 'National'],
            ['date' => '2026-05-28', 'name' => 'Youm-e-Takbeer / Eid-ul-Azha (Day 2)', 'type' => 'National'],
            ['date' => '2026-05-29', 'name' => 'Eid-ul-Azha (Day 3)', 'type' => 'National'],
            ['date' => '2026-06-24', 'name' => 'Ashura (9 Muharram)', 'type' => 'National'],
            ['date' => '2026-06-25', 'name' => 'Ashura (10 Muharram)', 'type' => 'National'],
            ['date' => '2026-08-14', 'name' => 'Independence Day', 'type' => 'National'],
            ['date' => '2026-08-25', 'name' => 'Eid Milad-un-Nabi', 'type' => 'National'],
            ['date' => '2026-11-09', 'name' => 'Allama Iqbal Day', 'type' => 'National'],
            ['date' => '2026-12-25', 'name' => 'Quaid-e-Azam Day / Christmas', 'type' => 'National'],
            ['date' => '2026-12-26', 'name' => 'Day after Christmas (Christians holiday)', 'type' => 'National'],
        ];

        foreach ($holidays as $holiday) {
            DB::table('holidays')->updateOrInsert(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'type' => $holiday['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('holidays')
            ->whereIn('date', [
                '2026-02-05',
                '2026-03-21',
                '2026-03-22',
                '2026-03-23',
                '2026-05-01',
                '2026-05-27',
                '2026-05-28',
                '2026-05-29',
                '2026-06-24',
                '2026-06-25',
                '2026-08-14',
                '2026-08-25',
                '2026-11-09',
                '2026-12-25',
                '2026-12-26',
            ])
            ->delete();
    }
};
