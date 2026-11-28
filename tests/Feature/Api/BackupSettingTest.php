<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\BackupSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_creates_the_singleton_with_config_defaults(): void
    {
        config([
            'backup.schedule.local.enabled' => true,
            'backup.schedule.local.at' => '03:15',
            'backup.schedule.remote.enabled' => true,
            'backup.schedule.remote.at' => '04:45',
            'backup.schedule.local.daily' => 31,
            'backup.schedule.local.weekly' => 13,
            'backup.schedule.local.monthly' => 14,
            'backup.schedule.local.yearly' => 5,
            'backup.schedule.remote.daily' => 8,
            'backup.schedule.remote.weekly' => 5,
            'backup.schedule.remote.monthly' => 13,
            'backup.schedule.remote.yearly' => 4,
        ]);

        $settings = BackupSetting::current();

        $this->assertSame(BackupSetting::SINGLETON_ID, $settings->id);
        $this->assertTrue($settings->local_enabled);
        $this->assertSame('03:15', $settings->local_time);
        $this->assertTrue($settings->remote_enabled);
        $this->assertSame('04:45', $settings->remote_time);
        $this->assertSame(31, $settings->local_daily_days);
        $this->assertSame(13, $settings->local_weekly_weeks);
        $this->assertSame(14, $settings->local_monthly_months);
        $this->assertSame(5, $settings->local_yearly_years);
        $this->assertSame(8, $settings->remote_daily_days);
        $this->assertSame(5, $settings->remote_weekly_weeks);
        $this->assertSame(13, $settings->remote_monthly_months);
        $this->assertSame(4, $settings->remote_yearly_years);
        $this->assertDatabaseCount('backup_settings', 1);
    }

    public function test_current_returns_the_same_row_on_repeated_calls(): void
    {
        $first = BackupSetting::current();
        $second = BackupSetting::current();

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('backup_settings', 1);
    }

    public function test_current_reuses_an_existing_row(): void
    {
        $existing = BackupSetting::query()->create(['local_time' => '05:00']);

        $settings = BackupSetting::current();

        $this->assertSame($existing->id, $settings->id);
        $this->assertSame('05:00', $settings->local_time);
        $this->assertDatabaseCount('backup_settings', 1);
    }

    public function test_remote_configured_requires_all_four_disk_values(): void
    {
        $configured = [
            'bucket' => 'pos-alja-backups',
            'endpoint' => 'https://example.r2.cloudflarestorage.com',
            'key' => 'access-key',
            'secret' => 'secret-key',
        ];

        config(['filesystems.disks.backups-s3' => $configured]);

        $settings = BackupSetting::current();

        $this->assertTrue($settings->remoteConfigured());

        foreach (array_keys($configured) as $missing) {
            config(['filesystems.disks.backups-s3' => array_merge($configured, [$missing => ''])]);

            $this->assertFalse(
                $settings->remoteConfigured(),
                "Expected remote backup to be unconfigured without {$missing}.",
            );
        }
    }

    public function test_remote_configured_is_false_without_a_disk_configuration(): void
    {
        config(['filesystems.disks.backups-s3' => []]);

        $this->assertFalse(BackupSetting::current()->remoteConfigured());
    }

    public function test_remote_usage_percent_is_not_a_model_attribute(): void
    {
        $settings = BackupSetting::current();

        $this->assertArrayNotHasKey('remote_usage_percent', $settings->getAttributes());
    }
}
