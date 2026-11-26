<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Disks
    |--------------------------------------------------------------------------
    |
    | Disk names used for the local volume and the off-host (R2/S3) bucket.
    | Both disks are defined in config/filesystems.php.
    |
    */

    'disks' => [
        'local' => 'backups-local',
        'remote' => 'backups-s3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule And Retention Defaults
    |--------------------------------------------------------------------------
    |
    | These values seed the backup_settings singleton on first access. After
    | the row exists, the database is the source of truth; env values are
    | initial defaults only. Retention windows are daily/weekly/monthly/yearly.
    |
    */

    'schedule' => [
        'local' => [
            'enabled' => true,
            'at' => (string) env('BACKUP_LOCAL_AT', '02:00'),
            'daily' => 30,
            'weekly' => 12,
            'monthly' => 12,
            'yearly' => 3,
        ],
        'remote' => [
            'enabled' => (bool) env('BACKUP_REMOTE_ENABLED', false),
            'at' => (string) env('BACKUP_REMOTE_AT', '02:30'),
            'daily' => 7,
            'weekly' => 4,
            'monthly' => 12,
            'yearly' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dump Process
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('BACKUP_TIMEOUT', 600),

    'pg_dump' => (string) (env('BACKUP_PG_DUMP') ?: 'pg_dump'),

    'remote_quota_bytes' => ((int) env('BACKUP_REMOTE_QUOTA_MB', 10240)) * 1024 * 1024,

    'tmp_directory' => storage_path('app/backup-tmp'),

];
