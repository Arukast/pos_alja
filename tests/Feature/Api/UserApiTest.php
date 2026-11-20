<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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
