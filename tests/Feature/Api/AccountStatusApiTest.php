<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountStatusApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders(['Origin' => 'http://localhost:5173']);

        $this->branchOne = Branch::create(['name' => 'Branch One']);
        $this->branchTwo = Branch::create(['name' => 'Branch Two']);
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

    public function test_guest_cannot_toggle_user_status(): void
    {
        $cashier = $this->user('cashier');

        $this->patchJson("/api/v1/users/{$cashier->id}/status")->assertUnauthorized();
    }

    public function test_owner_toggles_user_status_and_audits_it(): void
    {
        $owner = $this->user('owner', null);
        $cashier = $this->user('cashier');

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$cashier->id}/status")
            ->assertOk()
            ->assertJsonPath('user.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'is_active' => false]);

        $log = ActivityLog::query()->latest('created_at')->firstOrFail();
        $this->assertSame('UPDATE', $log->action);
        $this->assertSame(
            "Mengubah status aktif pengguna: {$cashier->username} menjadi nonaktif",
            $log->description
        );
        $this->assertSame(['is_active' => true], $log->payload['before']);
        $this->assertSame(['is_active' => false], $log->payload['after']);

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$cashier->id}/status")
            ->assertOk()
            ->assertJsonPath('user.is_active', true);
    }

    public function test_manager_toggles_only_own_branch_staff(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $own = $this->user('cashier', $this->branchOne);
        $other = $this->user('cashier', $this->branchTwo);

        $this->actingAs($manager)
            ->patchJson("/api/v1/users/{$own->id}/status")
            ->assertOk();

        $this->actingAs($manager)
            ->patchJson("/api/v1/users/{$other->id}/status")
            ->assertForbidden();

        $this->assertTrue($other->refresh()->is_active);
    }

    public function test_manager_cannot_toggle_privileged_target_in_own_branch(): void
    {
        $manager = $this->user('manager', $this->branchOne);
        $otherManager = $this->user('manager', $this->branchOne);

        $this->actingAs($manager)
            ->patchJson("/api/v1/users/{$otherManager->id}/status")
            ->assertForbidden();

        $this->assertTrue($otherManager->refresh()->is_active);
    }

    public function test_staff_cannot_toggle_user_status(): void
    {
        $designer = $this->user('designer', $this->branchOne);
        $cashier = $this->user('cashier', $this->branchOne);

        $this->actingAs($designer)
            ->patchJson("/api/v1/users/{$cashier->id}/status")
            ->assertForbidden();

        $this->assertTrue($cashier->refresh()->is_active);
    }

    public function test_user_cannot_toggle_own_status(): void
    {
        $owner = $this->user('owner', null);

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$owner->id}/status")
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active')
            ->assertJsonPath(
                'errors.is_active.0',
                'Anda tidak dapat menonaktifkan akun Anda sendiri dari Manajemen Pengguna. Gunakan menu Profil.'
            );

        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_status_service_blocks_last_active_owner(): void
    {
        $owner = $this->user('owner', null);
        $actor = $this->user('cashier', $this->branchOne);

        try {
            app(UserService::class)->toggleUserStatus($owner, $actor);
            $this->fail('Expected the last-owner guard to throw.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Pemilik aktif terakhir tidak dapat dinonaktifkan, dihapus, atau diturunkan perannya.',
                $exception->errors()['is_active'][0]
            );
        }

        $this->assertTrue($owner->refresh()->is_active);
    }

    private function user(string $role, ?Branch $branch = null, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'branch_id' => $branch?->id,
        ], $overrides));
    }
}
