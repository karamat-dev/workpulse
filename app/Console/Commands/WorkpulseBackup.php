<?php

namespace App\Console\Commands;

use App\Services\WorkpulseBackupService;
use Illuminate\Console\Command;

class WorkpulseBackup extends Command
{
    protected $signature = 'workpulse:backup {--reason=scheduled}';

    protected $description = 'Create a full muSharp database and local files backup';

    public function handle(WorkpulseBackupService $backups): int
    {
        $backup = $backups->create((string) $this->option('reason'));

        $this->info('Backup created: '.$backup['name']);

        return self::SUCCESS;
    }
}
