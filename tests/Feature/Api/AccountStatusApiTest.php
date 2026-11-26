<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders(['Origin' => 'http://localhost:5173']);

        $this->branchOne = Branch::create(['name' => 'Branch One']);
    }

    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = $this->user('cashier', $this->branchOne, ['is_active' => false]);

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Akun atau cabang Anda tidak aktif. Silakan hubungi pemilik.');

        $this->assertGuest('web');
    }

    public function test_inactive_branch_and_trashed_branch_return_401(): void
    {
        $user = $this->user('cashier', $this->branchOne);
        $this->branchOne->update(['is_active' => false]);

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->assertGuest('web');

        $this->branchOne->update(['is_active' => true]);
        $this->branchOne->delete();

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->assertGuest('web');
    }

    public function test_inactive_owner_is_401ed_on_owner_list_endpoint(): void
    {
        $owner = $this->user('owner', null, ['is_active' => false]);

        $this->actingAs($owner)->getJson('/api/v1/users')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Akun atau cabang Anda tidak aktif. Silakan hubungi pemilik.');

        $this->assertGuest('web');
    }

    public function test_owner_is_exempt_from_branch_checks(): void
    {
        $owner = $this->user('owner', null);
        $ownerOnBranch = $this->user('owner', $this->branchOne);
        $this->branchOne->update(['is_active' => false]);

        $this->actingAs($owner)->getJson('/api/v1/auth/me')->assertOk();
        $this->actingAs($owner)->getJson('/api/v1/users')->assertOk();
        $this->actingAs($ownerOnBranch)->getJson('/api/v1/auth/me')->assertOk();
        $this->actingAs($ownerOnBranch)->getJson('/api/v1/users')->assertOk();
    }

    public function test_non_owner_with_null_branch_is_not_401ed(): void
    {
        $user = $this->user('production', null);

        $this->actingAs($user)->getJson('/api/v1/auth/me')->assertOk();
        $this->actingAs($user)->getJson('/api/v1/waste/options')
            ->assertOk()
            ->assertJsonPath('designers', [])
            ->assertJsonPath('materials', []);
    }

    private function user(string $role, ?Branch $branch = null, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'branch_id' => $branch?->id,
        ], $overrides));
    }
}
