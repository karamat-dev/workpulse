<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WorkpulseAttendanceReminder extends Command
{
    protected $signature = 'workpulse:attendance:remind-missed-clockin';

    protected $description = 'Notify employees who have not clocked in by 11:30 AM on working weekdays';

    public function handle(): int
    {
        $now = now();

        if ($now->isWeekend()) {
            $this->info('Weekend detected. No attendance reminders sent.');

            return self::SUCCESS;
        }

        $today = $now->toDateString();
        $notificationType = 'attendance_clock_in_reminder';
        $summaryNotificationType = 'attendance_clock_in_summary';

        $employees = DB::table('users')
            ->join('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
            ->where('users.role', 'employee')
            ->whereIn('employee_profiles.status', ['Active', 'Probation'])
            ->select([
                'users.id',
                'users.name',
                'users.employee_code',
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

        $skipUserIds = array_unique(array_merge($clockedInUserIds, $leaveUserIds, $alreadyNotifiedUserIds));

        $targets = $employees->filter(fn ($employee) => !in_array($employee->id, $skipUserIds, true))->values();

        foreach ($targets as $employee) {
            DB::table('employee_notifications')->insert([
                'user_id' => $employee->id,
                'type' => $notificationType,
                'title' => 'Clock-in reminder',
                'message' => 'You have not clocked in yet. Please mark your attendance if you are working today.',
                'reference_type' => 'attendance',
                'reference_code' => $today,
                'meta' => json_encode([
                    'date' => $today,
                    'sent_at' => $now->toDateTimeString(),
                    'employee_code' => $employee->employee_code,
                ]),
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $summaryRecipients = DB::table('users')
            ->whereIn('role', ['admin', 'hr'])
            ->select(['id', 'role'])
            ->get();

        $alreadySummarizedUserIds = DB::table('employee_notifications')
            ->where('type', $summaryNotificationType)
            ->whereDate('created_at', $today)
            ->pluck('user_id')
            ->all();

        $summaryCount = $targets->count();

        foreach ($summaryRecipients as $recipient) {
            if (in_array($recipient->id, $alreadySummarizedUserIds, true)) {
                continue;
            }

            DB::table('employee_notifications')->insert([
                'user_id' => $recipient->id,
                'type' => $summaryNotificationType,
                'title' => 'Attendance summary',
                'message' => $summaryCount > 0
                    ? $summaryCount.' employees have not clocked in by 11:30 AM.'
                    : 'All employees have clocked in by 11:30 AM.',
                'reference_type' => 'attendance',
                'reference_code' => $today,
                'meta' => json_encode([
                    'date' => $today,
                    'missing_clock_in_count' => $summaryCount,
                    'recipient_role' => $recipient->role,
                ]),
                'is_read' => false,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->info('Attendance reminders sent: '.$targets->count());

        return self::SUCCESS;
    }
}
