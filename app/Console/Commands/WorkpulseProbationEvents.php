<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkpulseProbationEvents extends Command
{
    protected $signature = 'workpulse:events:probation {--days=30 : Lookahead days}';

    protected $description = 'Generate probation confirmation reminders';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $start = now()->startOfDay();
        $end = $start->copy()->addDays($days)->endOfDay();

        $matches = DB::table('employee_profiles')
            ->join('users', 'users.id', '=', 'employee_profiles.user_id')
            ->whereNotNull('employee_profiles.probation_end_date')
            ->whereBetween('employee_profiles.probation_end_date', [$start->toDateString(), $end->toDateString()])
            ->select(['users.name', 'employee_profiles.probation_end_date'])
            ->get();

        foreach ($matches as $m) {
            $d = now()->parse($m->probation_end_date)->startOfDay();

            DB::table('events')->updateOrInsert(
                [
                    'type' => 'probation',
                    'title' => 'Probation ends: '.$m->name,
                    'starts_at' => $d->copy()->setTime(9, 0)->toDateTimeString(),
                ],
                [
                    'description' => 'Automated probation confirmation reminder',
                    'ends_at' => $d->copy()->setTime(18, 0)->toDateTimeString(),
                    'created_by_user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $this->info('Probation events generated.');

        return self::SUCCESS;
    }
}

