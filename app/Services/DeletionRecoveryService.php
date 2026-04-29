<?php

namespace App\Services;

use App\Mail\ManagerDeletionAlertMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DeletionRecoveryService
{
    public function record(string $type, string $label, array $payload, ?int $deletedBy): void
    {
        if (!$this->hasRecoveryTable()) {
            return;
        }

        $deletedAt = now();
        $expiresAt = now()->addDays(4);

        DB::table('deletion_recovery_items')->insert([
            'item_type' => $type,
            'label' => $label,
            'payload' => json_encode($payload),
            'deleted_by' => $deletedBy,
            'deleted_at' => $deletedAt,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->emailManagersIfAdminDeleted($type, $label, $deletedBy, $deletedAt->toDateTimeString(), $expiresAt->toDateTimeString());
    }

    public function list(int $days = 4): array
    {
        if (!$this->hasRecoveryTable()) {
            return [];
        }

        $this->pruneExpired();

        return DB::table('deletion_recovery_items')
            ->leftJoin('users as deleted_by_user', 'deleted_by_user.id', '=', 'deletion_recovery_items.deleted_by')
            ->whereNull('deletion_recovery_items.restored_at')
            ->where('deletion_recovery_items.expires_at', '>=', now())
            ->where('deletion_recovery_items.deleted_at', '>=', now()->subDays($days))
            ->orderByDesc('deletion_recovery_items.deleted_at')
            ->limit(100)
            ->get([
                'deletion_recovery_items.id',
                'deletion_recovery_items.item_type',
                'deletion_recovery_items.label',
                'deletion_recovery_items.deleted_at',
                'deletion_recovery_items.expires_at',
                'deleted_by_user.name as deleted_by_name',
            ])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'type' => $item->item_type,
                'label' => $item->label,
                'deletedAt' => (string) $item->deleted_at,
                'recoverableUntil' => (string) $item->expires_at,
                'deletedBy' => $item->deleted_by_name ?: 'Admin',
            ])
            ->values()
            ->all();
    }

    public function restore(int $id, int $restoredBy): void
    {
        if (!$this->hasRecoveryTable()) {
            throw new RuntimeException('Recovery storage is not available on this installation.');
        }

        $item = DB::table('deletion_recovery_items')
            ->where('id', $id)
            ->whereNull('restored_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (!$item) {
            throw new RuntimeException('Recovery item was not found or has expired.');
        }

        $payload = json_decode((string) $item->payload, true) ?: [];

        DB::transaction(function () use ($item, $payload, $restoredBy) {
            match ($item->item_type) {
                'employee' => $this->restoreEmployee($payload),
                'announcement' => $this->restoreAnnouncement($payload),
                'department' => $this->restoreTableRow('departments', $payload),
                'holiday' => $this->restoreTableRow('holidays', $payload),
                'leave_type' => $this->restoreTableRow('leave_types', $payload),
                'policy' => $this->restoreTableRow('company_policies', $payload),
                'shift' => $this->restoreTableRow('shifts', $payload),
                'notification' => $this->restoreNotification($payload),
                'attendance_regulation' => $this->restoreTableRow('attendance_regulation_requests', $payload),
                default => throw new RuntimeException('This recovery type is not supported.'),
            };

            DB::table('deletion_recovery_items')->where('id', $item->id)->update([
                'restored_at' => now(),
                'restored_by' => $restoredBy,
                'updated_at' => now(),
            ]);
        });
    }

    public function captureTableRow(string $table, string $type, string $label, string $keyColumn, mixed $keyValue, ?int $deletedBy, array $extra = []): void
    {
        $row = DB::table($table)->where($keyColumn, $keyValue)->first();
        if (!$row) {
            return;
        }

        $this->record($type, $label, [
            'table' => $table,
            'keyColumn' => $keyColumn,
            'row' => (array) $row,
            ...$extra,
        ], $deletedBy);
    }

    private function restoreEmployee(array $payload): void
    {
        $user = $payload['user'] ?? null;
        $profile = $payload['profile'] ?? null;

        if (!$user || !$profile) {
            throw new RuntimeException('Employee recovery data is incomplete.');
        }

        DB::table('users')->where('id', $user['id'])->update($this->cleanRow($user));
        DB::table('employee_profiles')->updateOrInsert(
            ['user_id' => $profile['user_id']],
            $this->cleanRow($profile)
        );
    }

    private function restoreAnnouncement(array $payload): void
    {
        $this->restoreTableRow('announcements', $payload);

        foreach (($payload['recipients'] ?? []) as $recipient) {
            DB::table('announcement_recipients')->updateOrInsert(
                [
                    'announcement_id' => $recipient['announcement_id'],
                    'user_id' => $recipient['user_id'],
                ],
                $this->cleanRow($recipient)
            );
        }
    }

    private function restoreNotification(array $payload): void
    {
        $rows = $payload['rows'] ?? [];
        foreach ($rows as $row) {
            DB::table('employee_notifications')->updateOrInsert(
                ['id' => $row['id']],
                $this->cleanRow($row)
            );
        }
    }

    private function restoreTableRow(string $expectedTable, array $payload): void
    {
        $table = $payload['table'] ?? null;
        $row = $payload['row'] ?? null;

        if ($table !== $expectedTable || !$row) {
            throw new RuntimeException('Recovery data does not match the requested restore type.');
        }

        $keyColumn = $payload['keyColumn'] ?? 'id';
        DB::table($table)->updateOrInsert(
            [$keyColumn => $row[$keyColumn]],
            $this->cleanRow($row)
        );

        if ($table === 'departments' && !empty($payload['affectedProfileIds'])) {
            DB::table('employee_profiles')
                ->whereIn('id', $payload['affectedProfileIds'])
                ->update(['department_id' => $row['id'], 'updated_at' => now()]);
        }

        if ($table === 'shifts' && !empty($payload['affectedProfileIds'])) {
            DB::table('employee_profiles')
                ->whereIn('id', $payload['affectedProfileIds'])
                ->update(['shift_id' => $row['id'], 'updated_at' => now()]);
        }
    }

    private function cleanRow(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [$key => is_array($value) ? json_encode($value) : $value])
            ->all();
    }

    private function pruneExpired(): void
    {
        if (!$this->hasRecoveryTable()) {
            return;
        }

        DB::table('deletion_recovery_items')
            ->whereNull('restored_at')
            ->where('expires_at', '<', now()->subDay())
            ->delete();
    }

    private function hasRecoveryTable(): bool
    {
        static $hasTable;

        if ($hasTable !== null) {
            return $hasTable;
        }

        try {
            $hasTable = Schema::hasTable('deletion_recovery_items');
        } catch (\Throwable) {
            $hasTable = false;
        }

        return $hasTable;
    }

    private function emailManagersIfAdminDeleted(string $type, string $label, ?int $deletedBy, string $deletedAt, string $expiresAt): void
    {
        if (!$deletedBy) {
            return;
        }

        $admin = DB::table('users')->where('id', $deletedBy)->first(['name', 'role']);
        if (($admin->role ?? null) !== 'admin') {
            return;
        }

        $managerEmails = DB::table('users')
            ->where('role', 'manager')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        foreach ($managerEmails as $email) {
            try {
                Mail::to($email)->send(new ManagerDeletionAlertMail(
                    itemType: $type,
                    label: $label,
                    deletedBy: (string) ($admin->name ?: 'Admin'),
                    deletedAt: $deletedAt,
                    recoverableUntil: $expiresAt,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
