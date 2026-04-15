<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkpulseAnniversaryEvents extends Command
{
    protected $signature = 'workpulse:events:anniversary {--days=7 : Lookahead days}';

    protected $description = 'Generate joining anniversary events from employee profiles';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $today = now()->startOfDay();

        for ($i = 0; $i <= $days; $i++) {
            $d = $today->copy()->addDays($i);
            $month = (int) $d->format('m');
            $day = (int) $d->format('d');

            $matches = DB::table('employee_profiles')
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->whereNotNull('employee_profiles.date_of_joining')
                ->whereMonth('employee_profiles.date_of_joining', $month)
                ->whereDay('employee_profiles.date_of_joining', $day)
                ->select(['users.name', 'employee_profiles.date_of_joining'])
                ->get();

            foreach ($matches as $m) {
                DB::table('events')->updateOrInsert(
                    [
                        'type' => 'anniversary',
                        'title' => 'Anniversary: '.$m->name,
                        'starts_at' => $d->copy()->setTime(9, 0)->toDateTimeString(),
                    ],
                    [
                        'description' => 'Automated work anniversary reminder',
                        'ends_at' => $d->copy()->setTime(18, 0)->toDateTimeString(),
                        'created_by_user_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $this->info('Anniversary events generated.');

        return self::SUCCESS;
    }
}

