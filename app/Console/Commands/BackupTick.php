<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupSetting;
use App\Services\Backup\BackupDispatcher;
use App\Services\BackupService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BackupTick extends Command
{
    protected $signature = 'backup:tick';

    protected $description = 'Dispatch scheduled backups that are due.';

    public function handle(BackupService $service, BackupDispatcher $dispatcher): int
    {
        $settings = BackupSetting::current();

        foreach (['local', 'remote'] as $destination) {
            $this->dispatchIfDue($settings, $destination, $service, $dispatcher);
        }

        return self::SUCCESS;
    }

    private function dispatchIfDue(
        BackupSetting $settings,
        string $destination,
        BackupService $service,
        BackupDispatcher $dispatcher,
    ): void {
        if (! (bool) $settings->getAttribute($destination.'_enabled')) {
            return;
        }

        $due = now()->copy()->setTimeFromTimeString((string) $settings->getAttribute($destination.'_time'));

        if (now()->lt($due)) {
            return;
        }

        $lastDate = $settings->getAttribute('last_'.$destination.'_date');

        if ($lastDate instanceof CarbonInterface && $lastDate->isToday()) {
            return;
        }

        $backup = Backup::query()->create([
            'trigger' => 'scheduled',
            'destination' => $destination,
            'status' => 'queued',
        ]);

        $today = today()->toDateString();

        try {
            $dispatcher->dispatch($backup->id);
        } catch (Throwable $e) {
            Log::error('Scheduled backup dispatch failed.', [
                'backup_id' => $backup->id,
                'trigger' => $backup->trigger,
                'destination' => $destination,
                'user_id' => null,
                'error' => $e->getMessage(),
            ]);

            $service->fail($backup->id, $e);
        } finally {
            $settings->forceFill([
                'last_'.$destination.'_date' => $today,
            ])->save();
        }
    }
}
