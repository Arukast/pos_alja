<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Modules\Audit\Jobs\RunBackup;

final class QueueBackupDispatcher implements BackupDispatcher
{
    public function dispatch(string $backupId): void
    {
        RunBackup::dispatch($backupId);
    }
}
