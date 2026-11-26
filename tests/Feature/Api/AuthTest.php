<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders(['Origin' => 'http://localhost:5173']);

        $this->branch = Branch::create(['name' => 'Branch One']);
    }

    public function test_protected_auth_routes_return_401_for_guests(): void
    {
        $routes = [
            ['post', '/api/v1/auth/logout'],
            ['get', '/api/v1/auth/me'],
            ['put', '/api/v1/auth/profile'],
            ['delete', '/api/v1/auth/profile'],
            ['put', '/api/v1/auth/password'],
            ['post', '/api/v1/auth/confirm-password'],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
    }

    public function test_login_uses_username_and_returns_user(): void
    {
        $user = $this->user('owner', ['username' => 'owner']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'owner',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.username', 'owner')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.branch.id', $this->branch->id)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_bad_password_with_generic_message(): void
    {
        $this->user('cashier', ['username' => 'cashier']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'cashier',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_login_rejects_unknown_username_with_generic_message(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'missing-user',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_login_rejects_inactive_user(): void
    {
        $this->user('cashier', ['username' => 'inactive', 'is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'inactive',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_login_rejects_user_of_inactive_branch(): void
    {
        $this->user('cashier', ['username' => 'inactive-branch']);
        $this->branch->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'inactive-branch',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_login_rejects_user_of_trashed_branch(): void
    {
        $this->user('cashier', ['username' => 'trashed-branch']);
        $this->branch->delete();

        $this->postJson('/api/v1/auth/login', [
            'username' => 'trashed-branch',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_owner_without_branch_and_owner_on_inactive_branch_can_log_in(): void
    {
        $this->user('owner', ['username' => 'owner-null', 'branch_id' => null]);
        $this->branch->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'owner-null',
            'password' => 'password',
        ])->assertOk();

        $this->user('owner', ['username' => 'owner-inactive-branch']);
        $this->postJson('/api/v1/auth/login', [
            'username' => 'owner-inactive-branch',
            'password' => 'password',
        ])->assertOk();
    }

    public function test_non_owner_without_branch_can_log_in(): void
    {
        $this->user('cashier', ['username' => 'cashier-null', 'branch_id' => null]);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'cashier-null',
            'password' => 'password',
        ])->assertOk();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        $this->user('cashier', ['username' => 'throttled']);
        RateLimiter::clear(Str::transliterate('throttled|127.0.0.1'));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'throttled',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'username' => 'throttled',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertGuest('web');
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_branch_and_never_password(): void
    {
        $user = $this->user('designer');

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.branch.id', $this->branch->id)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_cashier_cannot_update_profile(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Changed Name',
                'email' => 'changed@example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function test_owner_can_update_profile_and_verification_is_reset(): void
    {
        $user = $this->user('owner', [
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', [
                'name' => 'New Owner Name',
                'email' => 'new-owner@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'New Owner Name')
            ->assertJsonPath('user.email', 'new-owner@example.com');

        $user->refresh();

        $this->assertSame('New Owner Name', $user->name);
        $this->assertSame('new-owner@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_is_unchanged_when_email_is_unchanged(): void
    {
        $user = $this->user('owner', ['email' => 'same@example.com']);

        $this->actingAs($user)
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Same Email',
                'email' => 'same@example.com',
            ])
            ->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_password_can_be_updated(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertNoContent();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_routes_are_throttled(): void
    {
        $user = $this->user('cashier');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->putJson('/api/v1/auth/password', [
                    'current_password' => 'wrong-password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])
                ->assertStatus(422);
        }

        $this->actingAs($user)
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertStatus(429);
    }

    public function test_confirm_password_validates_current_user_password(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->postJson('/api/v1/auth/confirm-password', ['password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs($user)
            ->postJson('/api/v1/auth/confirm-password', ['password' => 'password'])
            ->assertNoContent();

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_owner_can_deactivate_own_account(): void
    {
        $owner = $this->user('owner');
        $this->user('owner'); // second active owner

        $this->actingAs($owner)
            ->deleteJson('/api/v1/auth/profile', ['password' => 'password'])
            ->assertNoContent();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_active' => false]);
        $this->assertNotSoftDeleted('users', ['id' => $owner->id]);
        $this->assertGuest('web');

        $log = ActivityLog::query()->where('model_id', $owner->id)->where('action', 'UPDATE')->first();
        $this->assertNotNull($log);
        $this->assertSame("Menonaktifkan akun sendiri: {$owner->username}", $log->description);
        $this->assertSame(['before' => ['is_active' => true], 'after' => ['is_active' => false]], $log->payload);
    }

    public function test_last_owner_cannot_deactivate_own_account(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->deleteJson('/api/v1/auth/profile', ['password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active')
            ->assertJsonPath(
                'errors.is_active.0',
                'Pemilik aktif terakhir tidak dapat dinonaktifkan, dihapus, atau diturunkan perannya.'
            );

        $this->assertTrue($owner->refresh()->is_active);
        $this->assertAuthenticatedAs($owner);
    }

    public function test_cashier_cannot_delete_own_account(): void
    {
        $user = $this->user('cashier');

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/profile', ['password' => 'password'])
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_delete_requires_the_current_password(): void
    {
        $user = $this->user('owner');

        $this->actingAs($user)
            ->deleteJson('/api/v1/auth/profile', ['password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertTrue($user->refresh()->is_active);
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'branch_id' => $this->branch->id,
        ], $overrides));
    }
}
