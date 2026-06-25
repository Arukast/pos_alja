<?php

namespace Tests\Feature;

use App\Livewire\Profile\DeleteUserForm;
use App\Livewire\Profile\UpdatePasswordForm;
use App\Livewire\Profile\UpdateProfileInformationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_for_owner_with_delete_form(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeLivewire(UpdateProfileInformationForm::class)
            ->assertSeeLivewire(UpdatePasswordForm::class)
            ->assertSeeLivewire(DeleteUserForm::class);
    }

    public function test_profile_page_is_displayed_for_cashier_without_delete_form(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeLivewire(UpdateProfileInformationForm::class)
            ->assertSeeLivewire(UpdatePasswordForm::class)
            ->assertDontSeeLivewire(DeleteUserForm::class);
    }

    public function test_profile_information_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $component = Livewire::test(UpdateProfileInformationForm::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_information_cannot_be_updated_by_cashier(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user);

        $component = Livewire::test(UpdateProfileInformationForm::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component->assertHasErrors('name');

        $user->refresh();
        $this->assertNotSame('Test User', $user->name);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $component = Livewire::test(UpdateProfileInformationForm::class)
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_owner_can_delete_their_account(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $component = Livewire::test(DeleteUserForm::class)
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_cashier_cannot_delete_their_account(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user);

        $component = Livewire::test(DeleteUserForm::class)
            ->set('password', 'password')
            ->call('deleteUser');

        $component->assertStatus(403);

        $this->assertNotNull($user->fresh());
        $this->assertNull($user->fresh()->deleted_at);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $this->actingAs($user);

        $component = Livewire::test(DeleteUserForm::class)
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }
}
