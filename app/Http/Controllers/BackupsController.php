<?php

namespace App\Http\Controllers;

use App\Services\WorkpulseBackupService;
use App\Mail\ManagerDeletionAlertMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BackupsController extends Controller
{
    public function index(Request $request, WorkpulseBackupService $backups): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'backups' => $backups->list(10),
            'deletedBackups' => $request->user()?->role === 'manager'
                ? $backups->listDeleted(4)
                : [],
        ]);
    }

    public function store(WorkpulseBackupService $backups): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'backup' => $backups->create('manual'),
                'backups' => $backups->list(10),
                'deletedBackups' => request()->user()?->role === 'manager' ? $backups->listDeleted(4) : [],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function restore(string $backup, WorkpulseBackupService $backups): JsonResponse
    {
        try {
            $backups->restore($backup);

            return response()->json([
                'ok' => true,
                'message' => 'Backup restored successfully.',
                'backups' => $backups->list(10),
                'deletedBackups' => request()->user()?->role === 'manager' ? $backups->listDeleted(4) : [],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, string $backup, WorkpulseBackupService $backups): JsonResponse
    {
        try {
            $backups->delete($backup);
            $this->emailManagersIfAdminDeletedBackup($request, $backup);

            return response()->json([
                'ok' => true,
                'backups' => $backups->list(10),
                'deletedBackups' => request()->user()?->role === 'manager' ? $backups->listDeleted(4) : [],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function emailManagersIfAdminDeletedBackup(Request $request, string $backup): void
    {
        if (($request->user()?->role ?? null) !== 'admin') {
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
                    itemType: 'backup',
                    label: basename($backup),
                    deletedBy: (string) ($request->user()->name ?: 'Admin'),
                    deletedAt: now()->toDateTimeString(),
                    recoverableUntil: now()->addDays(4)->toDateTimeString(),
                ));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
