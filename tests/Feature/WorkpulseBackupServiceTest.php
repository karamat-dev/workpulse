<?php

namespace Tests\Feature;

use App\Services\WorkpulseBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WorkpulseBackupServiceTest extends TestCase
{
    public function test_backup_listing_labels_manual_and_auto_backups(): void
    {
        $backupDir = storage_path('framework/testing/backups-'.uniqid());
        Config::set('workpulse_backup.disk_path', $backupDir);
        File::ensureDirectoryExists($backupDir);

        try {
            File::put($backupDir.'/workpulse-20260428-010000-scheduled.zip', 'auto');
            File::put($backupDir.'/workpulse-20260428-120000-manual.zip', 'manual');

            $backups = app(WorkpulseBackupService::class)->list(10);

            $this->assertContains('Auto Backup', array_column($backups, 'typeLabel'));
            $this->assertContains('Manual Backup', array_column($backups, 'typeLabel'));
        } finally {
            File::deleteDirectory($backupDir);
        }
    }
}
