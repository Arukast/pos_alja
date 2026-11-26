<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Resources;

use App\Models\Backup;
use App\Models\BackupSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BackupSetting */
final class BackupSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quota = (int) config('backup.remote_quota_bytes');
        $remoteUsage = (int) Backup::query()->whereNotNull('remote_path')->sum('size_bytes');

        return [
            'local_enabled' => (bool) $this->local_enabled,
            'local_time' => $this->local_time,
            'remote_enabled' => (bool) $this->remote_enabled,
            'remote_time' => $this->remote_time,
            'local_daily_days' => (int) $this->local_daily_days,
            'local_weekly_weeks' => (int) $this->local_weekly_weeks,
            'local_monthly_months' => (int) $this->local_monthly_months,
            'local_yearly_years' => (int) $this->local_yearly_years,
            'remote_daily_days' => (int) $this->remote_daily_days,
            'remote_weekly_weeks' => (int) $this->remote_weekly_weeks,
            'remote_monthly_months' => (int) $this->remote_monthly_months,
            'remote_yearly_years' => (int) $this->remote_yearly_years,
            'local_usage_bytes' => (int) Backup::query()->whereNotNull('local_path')->sum('size_bytes'),
            'remote_usage_bytes' => $remoteUsage,
            'remote_quota_bytes' => $quota,
            'remote_usage_percent' => $quota > 0 ? round($remoteUsage / $quota * 100, 1) : 0.0,
            'remote_configured' => $this->remoteConfigured(),
            'pinned_count' => Backup::query()->where('is_pinned', true)->count(),
        ];
    }
}
