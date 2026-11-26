<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');
    }

    public function test_owner_can_list_all_users(): void
    {
        $owner = $this->user('owner', null);
        $cashier = $this->user('cashier', $this->branchOne);
        $otherManager = $this->user('manager', $this->branchTwo);
        $otherOwner = $this->user('owner', null);

        $this->actingAs($owner)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonFragment(['id' => $cashier->id])
            ->assertJsonFragment(['id' => $otherManager->id])
            ->assertJsonFragment(['id' => $otherOwner->id]);
    }

    public function test_manager_sees_only_branch_staff(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $cashier = $this->user('cashier', $this->branchOne);
        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $otherBranchCashier = $this->user('cashier', $this->branchTwo);
        $otherManager = $this->user('manager', $this->branchOne);
        $owner = $this->user('owner', null);

        $response = $this->actingAs($manager)->getJson('/api/v1/users')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing(
            [$cashier->id, $designer->id, $production->id],
            $ids
        );
        $this->assertNotContains($otherBranchCashier->id, $ids);
        $this->assertNotContains($otherManager->id, $ids);
        $this->assertNotContains($owner->id, $ids);
    }

    public function test_manager_can_view_own_branch_staff(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $cashier = $this->user('cashier', $this->branchOne);

        $this->actingAs($manager)
            ->getJson("/api/v1/users/{$cashier->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $cashier->id)
            ->assertJsonPath('user.branch.id', $this->branchOne->id);
    }

    public function test_manager_cannot_view_other_branch_or_privileged_users(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $otherBranchCashier = $this->user('cashier', $this->branchTwo);
        $owner = $this->user('owner', null);
        $peerManager = $this->user('manager', $this->branchOne);

        $this->actingAs($manager)
            ->getJson("/api/v1/users/{$otherBranchCashier->id}")
            ->assertForbidden();

        $this->actingAs($manager)
            ->getJson("/api/v1/users/{$owner->id}")
            ->assertForbidden();

        $this->actingAs($manager)
            ->getJson("/api/v1/users/{$peerManager->id}")
            ->assertForbidden();
    }

    public function test_cashier_cannot_list_or_view_users(): void
    {
        $cashier = $this->user('cashier', $this->branchOne);
        $peer = $this->user('designer', $this->branchOne);

        $this->actingAs($cashier)->getJson('/api/v1/users')->assertForbidden();
        $this->actingAs($cashier)->getJson("/api/v1/users/{$peer->id}")->assertForbidden();
    }

    public function test_user_search_matches_name_or_username(): void
    {
        $owner = $this->user('owner', null, ['name' => 'Search Owner']);
        $target = $this->user('cashier', $this->branchOne, [
            'name' => 'Ayu Lestari',
            'username' => 'ayu.cashier',
        ]);
        $this->user('designer', $this->branchOne, ['name' => 'Bima']);

        $this->actingAs($owner)
            ->getJson('/api/v1/users?search=Ayu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        $this->actingAs($owner)
            ->getJson('/api/v1/users?search=ayu.cashier')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_include_deleted_users_are_returned_only_on_request(): void
    {
        $owner = $this->user('owner', null);
        $deactivated = $this->user('cashier', $this->branchOne);
        $deactivated->delete();

        $this->actingAs($owner)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonMissing(['id' => $deactivated->id]);

        $this->actingAs($owner)
            ->getJson('/api/v1/users?include_deleted=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $deactivated->id]);
    }

    public function test_users_are_paginated_at_fifteen_per_page(): void
    {
        $owner = $this->user('owner', null);

        for ($index = 1; $index <= 16; $index++) {
            $this->user('cashier', $this->branchOne, ['name' => "Cashier {$index}"]);
        }

        $this->actingAs($owner)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 17)
            ->assertJsonCount(15, 'data');
    }

    public function test_owner_can_create_update_and_delete_users(): void
    {
        $owner = $this->user('owner', null);

        $created = $this->actingAs($owner)
            ->postJson('/api/v1/users', [
                'name' => 'New Cashier',
                'username' => 'new.cashier',
                'email' => 'new.cashier@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'cashier',
                'branch_id' => $this->branchOne->id,
            ])
            ->assertCreated()
            ->assertJsonPath('user.username', 'new.cashier')
            ->assertJsonPath('user.is_active', true);

        $userId = $created->json('user.id');

        $this->actingAs($owner)
            ->putJson("/api/v1/users/{$userId}", [
                'name' => 'Updated Cashier',
                'username' => 'new.cashier',
                'email' => 'new.cashier@example.com',
                'role' => 'cashier',
                'branch_id' => $this->branchOne->id,
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Cashier');

        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$userId}")
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $userId]);

        $this->actingAs($owner)
            ->postJson("/api/v1/users/{$userId}/restore")
            ->assertOk()
            ->assertJsonPath('user.deleted_at', null);
    }

    public function test_user_resource_exposes_active_flag_and_ignores_client_is_active_on_update(): void
    {
        $owner = $this->user('owner', null);
        $inactive = $this->user('cashier', $this->branchOne, ['is_active' => false]);

        $this->actingAs($owner)->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false]);

        $this->actingAs($owner)->putJson("/api/v1/users/{$inactive->id}", [
            'name' => 'Still Inactive',
            'username' => $inactive->username,
            'email' => $inactive->email,
            'role' => 'cashier',
            'branch_id' => $this->branchOne->id,
            'is_active' => true,
        ])->assertOk()->assertJsonPath('user.is_active', false);
    }

    public function test_restore_sets_user_active(): void
    {
        $owner = $this->user('owner', null);
        $removed = $this->user('cashier', $this->branchOne, ['is_active' => false]);
        $removed->delete();

        $this->actingAs($owner)->postJson("/api/v1/users/{$removed->id}/restore")
            ->assertOk()
            ->assertJsonPath('user.is_active', true);
    }

    public function test_manager_can_create_branch_staff_but_not_privileged_or_cross_branch_users(): void
    {
        $manager = $this->user('manager', $this->branchOne);

        $this->actingAs($manager)
            ->postJson('/api/v1/users', [
                'name' => 'Branch Cashier',
                'username' => 'branch.cashier',
                'email' => 'branch.cashier@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'cashier',
                'branch_id' => $this->branchOne->id,
            ])
            ->assertCreated();

        $this->actingAs($manager)
            ->postJson('/api/v1/users', [
                'name' => 'Sneaky Owner',
                'username' => 'sneaky.owner',
                'email' => 'sneaky.owner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'owner',
                'branch_id' => null,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->actingAs($manager)
            ->postJson('/api/v1/users', [
                'name' => 'Other Branch',
                'username' => 'other.branch',
                'email' => 'other.branch@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'cashier',
                'branch_id' => $this->branchTwo->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_manager_cannot_update_or_delete_other_branch_users(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $otherCashier = $this->user('cashier', $this->branchTwo);

        $this->actingAs($manager)
            ->putJson("/api/v1/users/{$otherCashier->id}", [
                'name' => 'Nope',
                'username' => $otherCashier->username,
                'email' => $otherCashier->email,
                'role' => 'cashier',
                'branch_id' => $this->branchTwo->id,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->deleteJson("/api/v1/users/{$otherCashier->id}")
            ->assertForbidden();
    }

    public function test_audit_payload_does_not_include_passwords(): void
    {
        $owner = $this->user('owner', null);

        $this->actingAs($owner)->postJson('/api/v1/users', [
            'name' => 'Audited User',
            'username' => 'audited.user',
            'email' => 'audited.user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
            'branch_id' => $this->branchOne->id,
        ])->assertCreated();

        $log = ActivityLog::query()
            ->where('action', 'CREATE')
            ->latest()
            ->firstOrFail();

        $this->assertArrayNotHasKey('password', $log->payload ?? []);
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    private function user(string $role, ?Branch $branch, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'branch_id' => $branch?->id,
        ], $overrides));
    }
}
