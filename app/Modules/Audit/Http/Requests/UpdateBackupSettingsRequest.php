<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Requests;

use App\Models\BackupSetting;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', BackupSetting::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'local_enabled' => ['required', 'boolean'],
            'local_time' => ['required', 'date_format:H:i'],
            'remote_enabled' => ['required', 'boolean'],
            'remote_time' => ['required', 'date_format:H:i'],
            'local_daily_days' => ['required', 'integer', 'between:7,90'],
            'local_weekly_weeks' => ['required', 'integer', 'between:4,52'],
            'local_monthly_months' => ['required', 'integer', 'between:6,24'],
            'local_yearly_years' => ['required', 'integer', 'between:1,10'],
            'remote_daily_days' => ['required', 'integer', 'between:7,90'],
            'remote_weekly_weeks' => ['required', 'integer', 'between:4,52'],
            'remote_monthly_months' => ['required', 'integer', 'between:6,24'],
            'remote_yearly_years' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
