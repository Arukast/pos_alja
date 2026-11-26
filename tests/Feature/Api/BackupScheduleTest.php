<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

final class BackupScheduleTest extends TestCase
{
    public function test_backup_tick_is_scheduled_every_minute_without_overlapping(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();

        $events = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => is_string($event->command)
                && str_contains($event->command, 'backup:tick'));

        $this->assertCount(1, $events);

        $event = $events->sole();

        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(5, $event->expiresAt);
    }
}
