<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class BackupSetting extends Model
{
    use HasFactory, HasUuids;

    public const SINGLETON_ID = '00000000-0000-4000-8000-000000000001';

    protected $fillable = [
        'local_enabled',
        'local_time',
        'remote_enabled',
        'remote_time',
        'local_daily_days',
        'local_weekly_weeks',
        'local_monthly_months',
        'local_yearly_years',
        'remote_daily_days',
        'remote_weekly_weeks',
        'remote_monthly_months',
        'remote_yearly_years',
        'last_local_date',
        'last_remote_date',
    ];

    protected $casts = [
        'local_enabled' => 'boolean',
        'remote_enabled' => 'boolean',
        'local_daily_days' => 'integer',
        'local_weekly_weeks' => 'integer',
        'local_monthly_months' => 'integer',
        'local_yearly_years' => 'integer',
        'remote_daily_days' => 'integer',
        'remote_weekly_weeks' => 'integer',
        'remote_monthly_months' => 'integer',
        'remote_yearly_years' => 'integer',
        'last_local_date' => 'date',
        'last_remote_date' => 'date',
    ];

    /**
     * Return the singleton row, creating it from config defaults on first access.
     */
    // Do not call inside an open PostgreSQL transaction: the unique-violation catch would hit an aborted transaction.
    public static function current(): self
    {
        $settings = static::query()->first();

        if ($settings instanceof self) {
            return $settings;
        }

        try {
            return static::query()->forceCreate([
                'id' => self::SINGLETON_ID,
                'local_enabled' => (bool) config('backup.schedule.local.enabled'),
                'local_time' => (string) config('backup.schedule.local.at'),
                'remote_enabled' => (bool) config('backup.schedule.remote.enabled'),
                'remote_time' => (string) config('backup.schedule.remote.at'),
                'local_daily_days' => (int) config('backup.schedule.local.daily'),
                'local_weekly_weeks' => (int) config('backup.schedule.local.weekly'),
                'local_monthly_months' => (int) config('backup.schedule.local.monthly'),
                'local_yearly_years' => (int) config('backup.schedule.local.yearly'),
                'remote_daily_days' => (int) config('backup.schedule.remote.daily'),
                'remote_weekly_weeks' => (int) config('backup.schedule.remote.weekly'),
                'remote_monthly_months' => (int) config('backup.schedule.remote.monthly'),
                'remote_yearly_years' => (int) config('backup.schedule.remote.yearly'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return static::query()->firstOrFail();
        }
    }

    /**
     * Whether the remote disk has every credential required to upload.
     */
    public function remoteConfigured(): bool
    {
        $disk = (array) config('filesystems.disks.backups-s3', []);

        foreach (['bucket', 'endpoint', 'key', 'secret'] as $name) {
            if (blank($disk[$name] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
