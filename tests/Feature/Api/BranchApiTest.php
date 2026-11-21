<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_and_view_branches(): void
    {
        $owner = $this->user('owner', null);
        $branch = $this->branch('Main Branch');

        $this->actingAs($owner)
            ->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonFragment(['id' => $branch->id, 'name' => 'Main Branch']);

        $this->actingAs($owner)
            ->getJson("/api/v1/branches/{$branch->id}")
            ->assertOk()
            ->assertJsonPath('branch.id', $branch->id)
            ->assertJsonPath('branch.name', 'Main Branch');
    }

    public function test_non_owner_roles_cannot_list_or_view_branches(): void
    {
        $branch = $this->branch('Main Branch');

        foreach (['manager', 'cashier', 'designer', 'production'] as $role) {
            $user = $this->user($role, $branch);

            $this->actingAs($user)->getJson('/api/v1/branches')->assertForbidden();
            $this->actingAs($user)->getJson("/api/v1/branches/{$branch->id}")->assertForbidden();
        }
    }

    public function test_branch_search_matches_name_or_code(): void
    {
        $owner = $this->user('owner', null);
        $this->branch('Main Branch', 'MAIN');
        $this->branch('Second Branch', 'SECOND');

        $this->actingAs($owner)
            ->getJson('/api/v1/branches?search=Second')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Second Branch');

        $this->actingAs($owner)
            ->getJson('/api/v1/branches?search=MAIN')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'MAIN');
    }

    public function test_include_deleted_includes_soft_deleted_branches(): void
    {
        $owner = $this->user('owner', null);
        $branch = $this->branch('Old Branch');
        $branch->delete();

        $this->actingAs($owner)
            ->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($owner)
            ->getJson('/api/v1/branches?include_deleted=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $branch->id);
    }

    public function test_branches_are_paginated_at_fifteen_per_page(): void
    {
        $owner = $this->user('owner', null);

        for ($index = 1; $index <= 16; $index++) {
            $this->branch("Branch {$index}");
        }

        $this->actingAs($owner)
            ->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonCount(15, 'data');
    }

    public function test_owner_can_create_update_toggle_and_delete_a_branch(): void
    {
        $owner = $this->user('owner', null);

        $created = $this->actingAs($owner)
            ->postJson('/api/v1/branches', [
                'name' => 'New Branch',
                'code' => 'NEW',
                'address' => 'Street 1',
                'phone' => '021-1',
            ])
            ->assertCreated()
            ->assertJsonPath('branch.name', 'New Branch')
            ->assertJsonPath('branch.code', 'NEW');

        $branchId = $created->json('branch.id');

        $this->actingAs($owner)
            ->putJson("/api/v1/branches/{$branchId}", [
                'name' => 'Renamed Branch',
                'code' => 'NEW',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('branch.name', 'Renamed Branch');

        $this->actingAs($owner)
            ->patchJson("/api/v1/branches/{$branchId}/status")
            ->assertOk()
            ->assertJsonPath('branch.is_active', false);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/branches/{$branchId}")
            ->assertNoContent();

        $this->assertSoftDeleted('branches', ['id' => $branchId]);
    }

    public function test_non_owner_cannot_write_branches(): void
    {
        $branch = $this->branch('Main Branch');
        $manager = $this->user('manager', $branch);

        $this->actingAs($manager)
            ->postJson('/api/v1/branches', ['name' => 'Nope', 'code' => 'NOPE'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->putJson("/api/v1/branches/{$branch->id}", ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->deleteJson("/api/v1/branches/{$branch->id}")
            ->assertForbidden();
    }

    public function test_branch_code_must_be_unique(): void
    {
        $owner = $this->user('owner', null);
        $this->branch('Main Branch', 'MAIN');

        $this->actingAs($owner)
            ->postJson('/api/v1/branches', [
                'name' => 'Duplicate',
                'code' => 'MAIN',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    private function branch(string $name, ?string $code = null): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => $code ?? Str::upper(Str::random(6)),
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
