<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\OrderUpdated;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_branch_write_broadcasts_on_the_owner_channel(): void
    {
        Event::fake([OrderUpdated::class]);

        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);

        $this->actingAs($owner)
            ->postJson('/api/v1/branches', [
                'name' => 'Realtime Branch',
                'code' => 'RT',
            ])
            ->assertCreated();

        Event::assertDispatched(
            OrderUpdated::class,
            fn (OrderUpdated $event) => $event->branchId === null
        );
    }

    public function test_successful_branch_scoped_write_broadcasts_on_the_branch_channel(): void
    {
        Event::fake([OrderUpdated::class]);

        $branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 10000,
                'type' => 'laba',
                'incurred_at' => now()->toDateString(),
                'description' => 'Test expense',
            ])
            ->assertCreated();

        Event::assertDispatched(
            OrderUpdated::class,
            fn (OrderUpdated $event) => $event->branchId === $branch->id
        );
    }

    public function test_reads_and_failed_writes_do_not_broadcast(): void
    {
        Event::fake([OrderUpdated::class]);

        $branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);
        $cashier = User::factory()->create(['role' => 'cashier', 'branch_id' => $branch->id]);

        $this->actingAs($cashier)->getJson('/api/v1/expenses')->assertOk();

        $this->actingAs($cashier)
            ->postJson('/api/v1/expenses', [
                'amount' => 0,
                'type' => 'laba',
                'incurred_at' => now()->toDateString(),
            ])
            ->assertStatus(422);

        Event::assertNotDispatched(OrderUpdated::class);
    }
}
