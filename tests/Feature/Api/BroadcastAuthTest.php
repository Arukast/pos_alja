<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\OrderUpdated;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
            'broadcasting.connections.pusher.options.cluster' => 'mt1',
        ]);

        require base_path('routes/channels.php');

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');
    }

    public function test_owner_can_authorize_branch_and_owner_channels(): void
    {
        $owner = $this->user('owner', null);

        $this->authorizeChannel($owner, 'private-branch.'.$this->branchOne->id)->assertOk();
        $this->authorizeChannel($owner, 'private-branch.'.$this->branchTwo->id)->assertOk();
        $this->authorizeChannel($owner, 'private-owners')->assertOk();
    }

    public function test_branch_user_can_authorize_only_own_branch(): void
    {
        $cashier = $this->user('cashier', $this->branchOne);

        $this->authorizeChannel($cashier, 'private-branch.'.$this->branchOne->id)->assertOk();
        $this->authorizeChannel($cashier, 'private-branch.'.$this->branchTwo->id)->assertForbidden();
        $this->authorizeChannel($cashier, 'private-owners')->assertForbidden();
    }

    public function test_manager_uses_the_same_branch_channel_rule(): void
    {
        $manager = $this->user('manager', $this->branchOne);

        $this->authorizeChannel($manager, 'private-branch.'.$this->branchOne->id)->assertOk();
        $this->authorizeChannel($manager, 'private-branch.'.$this->branchTwo->id)->assertForbidden();
    }

    public function test_guest_cannot_authorize_a_channel(): void
    {
        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-branch.'.$this->branchOne->id,
        ])->assertUnauthorized();
    }

    public function test_order_updated_broadcasts_on_branch_and_owner_channels(): void
    {
        $event = new OrderUpdated($this->branchOne->id);
        $names = array_map(fn ($channel): string => $channel->name, $event->broadcastOn());

        $this->assertContains('private-branch.'.$this->branchOne->id, $names);
        $this->assertContains('private-owners', $names);
    }

    public function test_global_order_updated_broadcasts_only_to_owners(): void
    {
        $event = new OrderUpdated;
        $names = array_map(fn ($channel): string => $channel->name, $event->broadcastOn());

        $this->assertSame(['private-owners'], $names);
    }

    private function authorizeChannel(User $user, string $channel)
    {
        return $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ]);
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
