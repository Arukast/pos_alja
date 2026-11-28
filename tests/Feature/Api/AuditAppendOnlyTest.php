<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_an_activity_log_throws_logic_exception(): void
    {
        $log = $this->log();

        $this->expectException(LogicException::class);

        $log->update(['description' => 'changed']);
    }

    public function test_deleting_an_activity_log_throws_logic_exception(): void
    {
        $log = $this->log();

        $this->expectException(LogicException::class);

        $log->delete();
    }

    public function test_force_deleting_a_branch_nulls_branch_id_and_keeps_log(): void
    {
        $branch = Branch::create(['name' => 'Branch One', 'code' => 'BR1']);
        $log = $this->log($branch);

        $branch->forceDelete();

        $fresh = ActivityLog::query()->find($log->id);

        $this->assertNotNull($fresh);
        $this->assertNull($fresh->branch_id);
    }

    private function log(?Branch $branch = null): ActivityLog
    {
        return ActivityLog::create([
            'branch_id' => $branch?->id,
            'user_id' => null,
            'username' => 'system',
            'role' => 'system',
            'action' => 'CREATE',
            'description' => 'Test log',
            'model_type' => null,
            'model_id' => null,
            'payload' => null,
        ]);
    }
}
