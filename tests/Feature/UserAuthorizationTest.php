<?php

namespace Tests\Feature;

use App\Livewire\Dashboards\Cashier;
use App\Livewire\Dashboards\Designer;
use App\Livewire\Dashboards\Manager;
use App\Livewire\Dashboards\Owner;
use App\Livewire\Dashboards\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests are redirected to login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responseProfile = $this->get('/profile');
        $responseProfile->assertRedirect('/login');
    }

    /**
     * Test Manager (manager) dashboard rendering.
     */
    public function test_manager_role_can_render_manager_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Manager::class);
        $response->assertDontSeeLivewire(Owner::class);
    }

    /**
     * Test Owner dashboard rendering.
     */
    public function test_owner_role_can_render_owner_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Owner::class);
        $response->assertDontSeeLivewire(Manager::class);
    }

    /**
     * Test Cashier (cashier) dashboard rendering.
     */
    public function test_cashier_role_can_render_cashier_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Cashier::class);
        $response->assertDontSeeLivewire(Manager::class);
    }

    /**
     * Test Designer (designer) dashboard rendering.
     */
    public function test_designer_role_can_render_designer_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'designer']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Designer::class);
        $response->assertDontSeeLivewire(Manager::class);
    }

    /**
     * Test Production Operator (production) dashboard rendering.
     */
    public function test_production_role_can_render_production_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'production']);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(Production::class);
        $response->assertDontSeeLivewire(Manager::class);
    }
}
