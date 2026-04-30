<?php

return [
    'disk_path' => env('WORKPULSE_BACKUP_PATH', storage_path('app/backups/musharp')),
    'keep_days' => (int) env('WORKPULSE_BACKUP_KEEP_DAYS', 10),
    'mysql_bin' => env('WORKPULSE_MYSQL_BIN', 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin'),
    'include_paths' => [
        public_path('uploads'),
        storage_path('app'),
    ],
];
