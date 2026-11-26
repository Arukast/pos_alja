<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\Backup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Per-destination GFS retention for backup dump files.
 *
 * Deliberately non-final so job tests can bind an anonymous subclass double.
 */
class BackupRetention
{
    private const PERIOD_FORMATS = ['Y-m-d', 'o-W', 'Y-m', 'Y'];

    /**
     * @param  array{daily:int, weekly:int, monthly:int, yearly:int}  $windows
     * @return int Number of deleted dump files.
     *
     * @throws InvalidArgumentException When any retention window is below 1.
     */
    public function prune(string $disk, array $windows, ?CarbonInterface $now = null): int
    {
        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $window) {
            if ($windows[$window] < 1) {
                throw new InvalidArgumentException("Retention window [{$window}] must be at least 1.");
            }
        }

        $storage = Storage::disk($disk);
        $timezone = (string) config('app.timezone');
        $now = $now === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::instance($now)->setTimezone($timezone);

        $files = [];

        foreach ($storage->allFiles() as $path) {
            if (preg_match('#^\d{4}/\d{2}/\d{2}/.+\.dump$#', $path) !== 1) {
                continue;
            }

            $date = CarbonImmutable::createFromFormat('!Y/m/d', substr($path, 0, 10), $timezone);

            if ($date === null) {
                continue;
            }

            $files[$path] = $date;
        }

        if ($files === []) {
            return 0;
        }

        $periods = $this->periods($now, $windows);
        $pinned = Backup::query()->where('is_pinned', true)->pluck('filename')->all();

        $paths = array_keys($files);
        rsort($paths, SORT_STRING);

        $keep = [];
        $seen = [];

        foreach ($paths as $path) {
            $protect = $path === $paths[0] || in_array(basename($path), $pinned, true);

            foreach (self::PERIOD_FORMATS as $format) {
                $period = $files[$path]->format($format);

                if (isset($periods[$format][$period]) && ! isset($seen[$format][$period])) {
                    $seen[$format][$period] = true;
                    $protect = true;
                }
            }

            if ($protect) {
                $keep[$path] = true;
            }
        }

        $pathColumn = $disk === config('backup.disks.local') ? 'local_path' : 'remote_path';
        $deleted = 0;

        foreach ($paths as $path) {
            if (isset($keep[$path])) {
                continue;
            }

            // Accepted non-atomic gap: file is deleted before the path is nulled; S3 cannot join the DB transaction, download 410/existence covers it.
            $storage->delete($path);
            Backup::query()->where($pathColumn, $path)->update([$pathColumn => null]);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * @param  array{daily:int, weekly:int, monthly:int, yearly:int}  $windows
     * @return array<string, array<string, true>>
     */
    private function periods(CarbonImmutable $now, array $windows): array
    {
        $periods = ['Y-m-d' => [], 'o-W' => [], 'Y-m' => [], 'Y' => []];

        for ($i = 0; $i < $windows['daily']; $i++) {
            $periods['Y-m-d'][$now->subDays($i)->format('Y-m-d')] = true;
        }

        $week = $now->startOfWeek();
        for ($i = 0; $i < $windows['weekly']; $i++) {
            $periods['o-W'][$week->subWeeks($i)->format('o-W')] = true;
        }

        $month = $now->startOfMonth();
        for ($i = 0; $i < $windows['monthly']; $i++) {
            $periods['Y-m'][$month->subMonths($i)->format('Y-m')] = true;
        }

        $year = $now->startOfYear();
        for ($i = 0; $i < $windows['yearly']; $i++) {
            $periods['Y'][$year->subYears($i)->format('Y')] = true;
        }

        return $periods;
    }
}
