<?php

namespace App\Console\Commands;

use App\Mail\ClockInReminderMail;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WorkpulseAttendanceReminder extends Command
{
    protected $signature = 'workpulse:attendance:remind-missed-clockin';

    protected $description = 'Email employees who have not clocked in within 30 minutes after shift start';

    public function handle(): int
    {
        $now = now();

        if ($now->isWeekend()) {
            $this->info('Weekend detected. No attendance reminders sent.');

            return self::SUCCESS;
        }

        $today = $now->toDateString();
        $notificationType = 'attendance_clock_in_reminder';

        $employees = DB::table('users')
            ->join('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->leftJoin('shifts', 'shifts.id', '=', 'employee_profiles.shift_id')
            ->whereIn('users.role', ['employee', 'manager', 'hr', 'admin'])
            ->whereIn('employee_profiles.status', ['Active', 'Probation'])
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.employee_code',
                'shifts.start_time',
                'shifts.working_days',
            ])
            ->get();

        $clockedInUserIds = DB::table('attendance_punches')
            ->where('date', $today)
            ->where('type', 'clock_in')
            ->pluck('user_id')
            ->all();

        $leaveUserIds = DB::table('leave_requests')
            ->where('status', 'Approved')
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today)
            ->pluck('user_id')
            ->all();

        $alreadyNotifiedUserIds = DB::table('employee_notifications')
            ->where('type', $notificationType)
            ->whereDate('created_at', $today)
            ->pluck('user_id')
            ->all();

        $skipUserIds = array_unique(array_map('intval', array_merge($clockedInUserIds, $leaveUserIds, $alreadyNotifiedUserIds)));

        $targets = $employees
            ->filter(function ($employee) use ($now, $today, $skipUserIds) {
                if (in_array((int) $employee->id, $skipUserIds, true)) {
                    return false;
                }

                if (!$this->isWorkingDay($now, (string) ($employee->working_days ?? 'Mon-Fri'))) {
                    return false;
                }

                $shiftStartTime = $employee->start_time ?: '11:00:00';
                $reminderAt = Carbon::parse($today.' '.$shiftStartTime)->addMinutes(30);

                return $now->greaterThanOrEqualTo($reminderAt);
            })
            ->values();

        foreach ($targets as $employee) {
            $shiftStartTime = substr((string) ($employee->start_time ?: '11:00:00'), 0, 5);

            DB::table('employee_notifications')->insert([
                'user_id' => $employee->id,
                'type' => $notificationType,
                'title' => 'Clock-in reminder',
                'message' => 'Your shift started at '.$shiftStartTime.'. Please clock in if you are working today.',
                'reference_type' => 'attendance',
                'reference_code' => $today,
                'meta' => json_encode([
                    'date' => $today,
                    'sent_at' => $now->toDateTimeString(),
                    'employee_code' => $employee->employee_code,
                    'shift_start' => $shiftStartTime,
                ]),
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($employee->email) {
                try {
                    Mail::to($employee->email)->send(new ClockInReminderMail(
                        employeeName: (string) $employee->name,
                        shiftStart: $shiftStartTime,
                        date: $today,
                        loginUrl: url('/workpulse'),
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->info('Attendance reminders sent: '.$targets->count());

        return self::SUCCESS;
    }

    private function isWorkingDay(Carbon $date, string $workingDays): bool
    {
        $normalized = strtolower(trim($workingDays));

        if ($normalized === '' || str_contains($normalized, 'mon-fri')) {
            return $date->isWeekday();
        }

        if (str_contains($normalized, 'sat') && str_contains($normalized, 'sun') && str_contains($normalized, 'mon')) {
            return true;
        }

        $map = [
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
            'sun' => 7,
        ];

        foreach ($map as $label => $dayNumber) {
            if (str_contains($normalized, $label) && $date->dayOfWeekIso === $dayNumber) {
                return true;
            }
        }

        return $date->isWeekday();
    }
}
