<?php

namespace App\Http\Controllers;

use App\Services\WorkpulseBackupService;
use Illuminate\Http\JsonResponse;
use Throwable;

class BackupsController extends Controller
{
    public function index(WorkpulseBackupService $backups): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'backups' => $backups->list(10),
        ]);
    }

    public function store(WorkpulseBackupService $backups): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'backup' => $backups->create('manual'),
                'backups' => $backups->list(10),
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
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $backup, WorkpulseBackupService $backups): JsonResponse
    {
        try {
            $backups->delete($backup);

            return response()->json([
                'ok' => true,
                'backups' => $backups->list(10),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
