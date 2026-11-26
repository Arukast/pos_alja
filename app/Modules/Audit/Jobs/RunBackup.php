<?php

declare(strict_types=1);

namespace App\Modules\Audit\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

final class RunBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public string $backupId)
    {
        $this->timeout = (int) config('backup.timeout');
    }

    public function handle(BackupService $service): void
    {
        $service->run($this->backupId);
    }

    public function failed(?Throwable $e): void
    {
        app(BackupService::class)->fail(
            $this->backupId,
            $e ?? new RuntimeException('Backup job failed without an exception.'),
        );
    }
}
