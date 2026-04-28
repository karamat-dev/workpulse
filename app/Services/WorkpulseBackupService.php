<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class WorkpulseBackupService
{
    public function list(int $limit = 10): array
    {
        $this->ensureBackupDir();

        return collect(File::files($this->backupDir()))
            ->filter(fn ($file) => $file->getExtension() === 'zip')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take($limit)
            ->map(fn ($file) => [
                'id' => $file->getFilename(),
                'name' => $file->getFilename(),
                'createdAt' => Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
                'size' => $file->getSize(),
                'sizeLabel' => $this->formatBytes($file->getSize()),
                'path' => $file->getPathname(),
            ])
            ->values()
            ->all();
    }

    public function create(string $reason = 'scheduled'): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required to create WorkPulse backups.');
        }

        $this->ensureBackupDir();
        $timestamp = now()->format('Ymd-His');
        $name = "workpulse-{$timestamp}-{$reason}.zip";
        $tempDir = storage_path("app/backups/tmp/workpulse-{$timestamp}-{$reason}");
        $zipPath = $this->backupDir().DIRECTORY_SEPARATOR.$name;

        File::ensureDirectoryExists($tempDir);

        try {
            $databasePath = $tempDir.DIRECTORY_SEPARATOR.'database.sql';
            $this->dumpDatabase($databasePath);

            File::put($tempDir.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
                'app' => 'WorkPulse',
                'created_at' => now()->toDateTimeString(),
                'reason' => $reason,
                'database' => Config::get('database.default'),
                'includes' => collect(Config::get('workpulse_backup.include_paths', []))
                    ->map(fn ($path) => $this->normalizePath((string) $path))
                    ->values()
                    ->all(),
            ], JSON_PRETTY_PRINT));

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create backup zip file.');
            }

            $zip->addFile($databasePath, 'database/database.sql');
            $zip->addFile($tempDir.DIRECTORY_SEPARATOR.'manifest.json', 'manifest.json');
            $this->addIncludedFiles($zip);
            $zip->close();

            $this->pruneOldBackups();

            return collect($this->list(100))
                ->firstWhere('name', $name)
                ?? ['id' => $name, 'name' => $name, 'createdAt' => now()->toDateTimeString(), 'size' => File::size($zipPath), 'sizeLabel' => $this->formatBytes(File::size($zipPath)), 'path' => $zipPath];
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    public function delete(string $backupName): void
    {
        $path = $this->resolveBackupPath($backupName);
        File::delete($path);
    }

    public function restore(string $backupName): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required to restore WorkPulse backups.');
        }

        $backupPath = $this->resolveBackupPath($backupName);

        $restoreDir = storage_path('app/backups/tmp/restore-'.now()->format('Ymd-His'));
        File::ensureDirectoryExists($restoreDir);

        try {
            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new RuntimeException('Unable to open backup zip file.');
            }
            $zip->extractTo($restoreDir);
            $zip->close();

            $databasePath = $restoreDir.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sql';
            if (!File::exists($databasePath)) {
                throw new RuntimeException('Backup package does not contain database/database.sql.');
            }

            $this->create('pre-restore');
            $this->restoreDatabase($databasePath);
            $this->restoreIncludedFiles($restoreDir.DIRECTORY_SEPARATOR.'files');
        } finally {
            if (File::exists($restoreDir)) {
                File::deleteDirectory($restoreDir);
            }
        }
    }

    private function dumpDatabase(string $outputPath): void
    {
        $connection = Config::get('database.default');

        if ($connection === 'mysql') {
            $config = Config::get('database.connections.mysql');
            $command = [
                $this->mysqlBinary('mysqldump.exe'),
                '--host='.$config['host'],
                '--port='.(string) $config['port'],
                '--user='.$config['username'],
                '--databases',
                $config['database'],
                '--routines',
                '--events',
                '--triggers',
                '--single-transaction',
                '--quick',
                '--add-drop-table',
            ];

            if (($config['password'] ?? '') !== '') {
                $command[] = '--password='.$config['password'];
            }

            $this->runProcessToFile($command, $outputPath);
            return;
        }

        if ($connection === 'sqlite') {
            $database = Config::get('database.connections.sqlite.database');
            if (!File::exists($database)) {
                throw new RuntimeException('SQLite database file was not found.');
            }
            File::copy($database, $outputPath);
            return;
        }

        throw new RuntimeException("Backup is not configured for {$connection} databases.");
    }

    private function restoreDatabase(string $sqlPath): void
    {
        $connection = Config::get('database.default');

        if ($connection === 'mysql') {
            $config = Config::get('database.connections.mysql');
            $command = [
                $this->mysqlBinary('mysql.exe'),
                '--host='.$config['host'],
                '--port='.(string) $config['port'],
                '--user='.$config['username'],
            ];

            if (($config['password'] ?? '') !== '') {
                $command[] = '--password='.$config['password'];
            }

            $process = new Process($command);
            $process->setInput(File::get($sqlPath));
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Database restore failed.');
            }
            return;
        }

        if ($connection === 'sqlite') {
            File::copy($sqlPath, Config::get('database.connections.sqlite.database'));
            return;
        }

        throw new RuntimeException("Restore is not configured for {$connection} databases.");
    }

    private function addIncludedFiles(ZipArchive $zip): void
    {
        foreach (Config::get('workpulse_backup.include_paths', []) as $path) {
            $path = (string) $path;
            if (!File::exists($path)) {
                continue;
            }

            $baseName = str_starts_with($this->normalizePath($path), $this->normalizePath(public_path()))
                ? 'public/'.basename($path)
                : 'storage/app';

            if (File::isFile($path)) {
                $zip->addFile($path, 'files/'.$baseName);
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $realPath = $this->normalizePath($file->getPathname());
                if (str_contains($realPath, $this->normalizePath(storage_path('app/backups')))) {
                    continue;
                }

                $relative = ltrim(str_replace($this->normalizePath($path), '', $realPath), '/');
                $zip->addFile($file->getPathname(), 'files/'.$baseName.'/'.$relative);
            }
        }
    }

    private function restoreIncludedFiles(string $filesRoot): void
    {
        $publicUploads = $filesRoot.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads';
        if (File::exists($publicUploads)) {
            File::copyDirectory($publicUploads, public_path('uploads'));
        }

        $storageApp = $filesRoot.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app';
        if (File::exists($storageApp)) {
            File::copyDirectory($storageApp, storage_path('app'));
        }
    }

    private function pruneOldBackups(): void
    {
        $keepDays = max(1, (int) Config::get('workpulse_backup.keep_days', 10));
        $cutoff = now()->subDays($keepDays)->getTimestamp();

        foreach (File::files($this->backupDir()) as $file) {
            if ($file->getExtension() === 'zip' && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }

    private function runProcessToFile(array $command, string $outputPath): void
    {
        $handle = fopen($outputPath, 'wb');
        if (!$handle) {
            throw new RuntimeException('Unable to write database dump.');
        }

        try {
            $process = new Process($command);
            $process->setTimeout(600);
            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Database dump failed.');
            }
        } finally {
            fclose($handle);
        }
    }

    private function resolveBackupPath(string $backupName): string
    {
        $name = basename($backupName);
        if (!str_ends_with($name, '.zip')) {
            throw new RuntimeException('Invalid backup file.');
        }

        $path = $this->backupDir().DIRECTORY_SEPARATOR.$name;
        $realBackupDir = realpath($this->backupDir());
        $realPath = realpath($path);

        if (!$realBackupDir || !$realPath || !str_starts_with($this->normalizePath($realPath), $this->normalizePath($realBackupDir))) {
            throw new RuntimeException('Backup file was not found.');
        }

        return $realPath;
    }

    private function mysqlBinary(string $binary): string
    {
        $path = rtrim((string) Config::get('workpulse_backup.mysql_bin'), '\\/').DIRECTORY_SEPARATOR.$binary;
        if (!File::exists($path)) {
            throw new RuntimeException("{$binary} was not found at {$path}. Set WORKPULSE_MYSQL_BIN in .env.");
        }

        return $path;
    }

    private function backupDir(): string
    {
        return (string) Config::get('workpulse_backup.disk_path');
    }

    private function ensureBackupDir(): void
    {
        File::ensureDirectoryExists($this->backupDir());
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
