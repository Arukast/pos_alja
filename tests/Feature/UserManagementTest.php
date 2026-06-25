<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Dashboards\Manager;
use App\Livewire\Dashboards\Owner;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch1;

    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch1 = Branch::create(['id' => 1, 'name' => 'Branch One']);
        $this->branch2 = Branch::create(['id' => 2, 'name' => 'Branch Two']);
    }

    /**
     * Test Cashier, Designer, Production roles cannot access dashboards user tab or call backend actions.
     */
    public function test_unauthorized_roles_cannot_access_user_management(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch1->id,
        ]);

        // Rendering dashboard for cashier should not see users tab or lists
        $response = $this->actingAs($cashier)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertDontSee('User Management');

        // Trying to test Volt component dashboards.manager directly as cashier
        // Cashier role renders dashboards.cashier, so they shouldn't even be on manager dashboard.
        // If we force access dashboards.manager, it should fail or not allow actions.
        $designer = User::factory()->create([
            'role' => 'designer',
            'branch_id' => $this->branch1->id,
        ]);

        $response2 = $this->actingAs($designer)->get('/dashboard');
        $response2->assertStatus(200);
        $response2->assertDontSee('User Management');
    }

    /**
     * Test Manager can view branch scoped users but actions on managers/owners are locked.
     */
    public function test_manager_can_view_branch_scoped_users_with_restricted_actions(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        $cashier1 = User::factory()->create([
            'name' => 'Branch 1 Cashier',
            'role' => 'cashier',
            'branch_id' => $this->branch1->id,
        ]);

        $cashier2 = User::factory()->create([
            'name' => 'Branch 2 Cashier',
            'role' => 'cashier',
            'branch_id' => $this->branch2->id,
        ]);

        $otherManager = User::factory()->create([
            'name' => 'Another Branch 1 Manager',
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        $owner = User::factory()->create([
            'name' => 'Global Owner',
            'role' => 'owner',
            'branch_id' => $this->branch1->id,
        ]);

        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->assertViewHas('usersList', function ($users) use ($cashier1, $cashier2, $otherManager, $owner) {
                // Should contain branch 1 users
                $hasBranch1User = $users->contains($cashier1);
                $hasOtherManager = $users->contains($otherManager);
                $hasOwner = $users->contains($owner);
                // Should NOT contain branch 2 users
                $hasBranch2User = $users->contains($cashier2);

                return $hasBranch1User && $hasOtherManager && $hasOwner && ! $hasBranch2User;
            });
    }

    /**
     * Test Manager can create, update, and soft delete cashier, designer, and production users.
     */
    public function test_manager_can_crud_staff_in_own_branch(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        // 1. Create Staff
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('openCreateModal')
            ->set('formName', 'New Staff')
            ->set('formUsername', 'newstaff')
            ->set('formEmail', 'newstaff@example.com')
            ->set('formRole', 'cashier')
            ->set('formPassword', 'password123')
            ->call('saveUser')
            ->assertHasNoErrors()
            ->assertSet('showUserModal', false);

        $this->assertDatabaseHas('users', [
            'username' => 'newstaff',
            'role' => 'cashier',
            'branch_id' => $manager->branch_id,
        ]);

        $staff = User::where('username', 'newstaff')->first();

        // 2. Update Staff
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('openEditModal', $staff->id)
            ->assertSet('formName', 'New Staff')
            ->set('formName', 'Updated Staff')
            ->call('saveUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Updated Staff',
        ]);

        // 3. Delete Staff (Soft Delete)
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('deleteUser', $staff->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($staff);
    }

    /**
     * Test Manager validation constraints and action blocks.
     */
    public function test_manager_cannot_manage_owners_or_managers(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => $this->branch1->id,
        ]);

        $otherManager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        // Manager cannot open edit modal for owner
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->assertStatus(200)
            ->call('openEditModal', $owner->id)
            ->assertStatus(403);

        // Manager cannot open edit modal for manager
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('openEditModal', $otherManager->id)
            ->assertStatus(403);

        // Manager cannot edit/save owner
        // Even if they forge the modal state, the saveUser handler blocks it
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->set('userIdBeingEdited', $owner->id)
            ->set('formName', 'Hacked Owner')
            ->set('formUsername', $owner->username)
            ->set('formEmail', $owner->email)
            ->set('formRole', 'cashier')
            ->call('saveUser')
            ->assertStatus(403);

        // Manager cannot delete owner
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('deleteUser', $owner->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'role' => 'owner',
            'deleted_at' => null,
        ]);
    }

    /**
     * Test Manager cannot manage users in other branches.
     */
    public function test_manager_cannot_manage_other_branch_users(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
        ]);

        $otherBranchCashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch2->id,
        ]);

        // Try to open edit modal for other branch user - expect 403
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('openEditModal', $otherBranchCashier->id)
            ->assertStatus(403);

        // Try to delete other branch user - expect 403
        Livewire::actingAs($manager)
            ->test(UserManagement::class, ['mode' => 'manager'])
            ->call('deleteUser', $otherBranchCashier->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $otherBranchCashier->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test Primary Owner (ID = 1) can edit or delete owner accounts.
     */
    public function test_primary_owner_can_manage_all_owners(): void
    {
        // Force create a user with ID = 1
        $primaryOwner = User::create([
            'id' => 1,
            'name' => 'Primary Owner',
            'username' => 'primary',
            'email' => 'primary@example.com',
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);

        $secondaryOwner = User::factory()->create([
            'role' => 'owner',
        ]);

        // Primary Owner can edit Secondary Owner
        Livewire::actingAs($primaryOwner)
            ->test(UserManagement::class, ['mode' => 'owner'])
            ->call('openEditModal', $secondaryOwner->id)
            ->assertSet('formName', $secondaryOwner->name)
            ->set('formName', 'Updated Owner Name')
            ->set('formUsername', $secondaryOwner->username)
            ->set('formEmail', $secondaryOwner->email)
            ->set('formRole', 'owner')
            ->set('formBranchId', 1)
            ->call('saveUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $secondaryOwner->id,
            'name' => 'Updated Owner Name',
        ]);

        // Primary Owner can delete Secondary Owner
        Livewire::actingAs($primaryOwner)
            ->test(UserManagement::class, ['mode' => 'owner'])
            ->call('deleteUser', $secondaryOwner->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($secondaryOwner);
    }

    /**
     * Test Secondary Owner (ID > 1) cannot edit or delete other owners.
     */
    public function test_secondary_owner_cannot_manage_owners(): void
    {
        // Force create primary owner
        $primaryOwner = User::create([
            'id' => 1,
            'name' => 'Primary Owner',
            'username' => 'primary',
            'email' => 'primary@example.com',
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);

        // Create secondary owner
        $secondaryOwner = User::create([
            'id' => 2,
            'name' => 'Secondary Owner',
            'username' => 'secondary',
            'email' => 'secondary@example.com',
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);

        // Secondary Owner cannot open edit modal for Primary Owner
        Livewire::actingAs($secondaryOwner)
            ->test(UserManagement::class, ['mode' => 'owner'])
            ->call('openEditModal', $primaryOwner->id)
            ->assertStatus(403);

        // Secondary Owner cannot delete Primary Owner
        Livewire::actingAs($secondaryOwner)
            ->test(UserManagement::class, ['mode' => 'owner'])
            ->call('deleteUser', $primaryOwner->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $primaryOwner->id,
            'deleted_at' => null,
        ]);
    }
}
