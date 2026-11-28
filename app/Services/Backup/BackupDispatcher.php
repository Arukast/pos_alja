<?php

declare(strict_types=1);

namespace App\Services\Backup;

interface BackupDispatcher
{
    public function dispatch(string $backupId): void;
}
