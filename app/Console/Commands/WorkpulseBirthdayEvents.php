<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkpulseBirthdayEvents extends Command
{
    protected $signature = 'workpulse:events:birthday {--days=7 : Lookahead days}';

    protected $description = 'Generate birthday events from employee profiles';

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
                ->whereNotNull('employee_profiles.date_of_birth')
                ->whereMonth('employee_profiles.date_of_birth', $month)
                ->whereDay('employee_profiles.date_of_birth', $day)
                ->select(['users.id as user_id', 'users.name'])
                ->get();

            foreach ($matches as $m) {
                DB::table('events')->updateOrInsert(
                    [
                        'type' => 'birthday',
                        'title' => 'Birthday: '.$m->name,
                        'starts_at' => $d->copy()->setTime(9, 0)->toDateTimeString(),
                    ],
                    [
                        'description' => 'Automated birthday reminder',
                        'ends_at' => $d->copy()->setTime(18, 0)->toDateTimeString(),
                        'created_by_user_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $this->info('Birthday events generated.');

        return self::SUCCESS;
    }
}

