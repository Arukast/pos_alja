<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\MachineManagement;
use App\Models\Branch;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MachineManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $managerA;

    private User $managerB;

    private User $cashier;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create([
            'name' => 'Branch A',
            'code' => 'BRA',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'name' => 'Branch B',
            'code' => 'BRB',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'username' => 'testowner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => null,
        ]);

        $this->managerA = User::create([
            'name' => 'Manager A',
            'email' => 'managera@example.com',
            'username' => 'managera',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => $this->branchA->id,
        ]);

        $this->managerB = User::create([
            'name' => 'Manager B',
            'email' => 'managerb@example.com',
            'username' => 'managerb',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => $this->branchB->id,
        ]);

        $this->cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'username' => 'testcashier',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => $this->branchA->id,
        ]);
    }

    /**
     * Cashier/unauthorized roles cannot access machine management.
     */
    public function test_unauthorized_roles_cannot_access_machine_management(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(MachineManagement::class)
            ->assertStatus(403);
    }

    /**
     * Owner can access machine management.
     */
    public function test_owner_can_access_machine_management(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(MachineManagement::class)
            ->assertStatus(200)
            ->assertSee('Printer & Press Machines', false);
    }

    /**
     * Manager can access machine management.
     */
    public function test_manager_can_access_machine_management(): void
    {
        $this->actingAs($this->managerA);

        Livewire::test(MachineManagement::class)
            ->assertStatus(200)
            ->assertSee('Printer & Press Machines', false);
    }

    /**
     * Owner can create global and branch-specific machines.
     */
    public function test_owner_can_create_machines(): void
    {
        $this->actingAs($this->owner);

        // Global machine
        Livewire::test(MachineManagement::class)
            ->call('openCreateModal')
            ->set('form.name', 'Global Press Machine')
            ->set('form.branch_id', null)
            ->set('form.cost', 1500.00)
            ->call('saveMachine')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('machines', [
            'name' => 'Global Press Machine',
            'branch_id' => null,
            'cost' => 1500.00,
        ]);

        // Branch machine
        Livewire::test(MachineManagement::class)
            ->call('openCreateModal')
            ->set('form.name', 'Branch A Printer')
            ->set('form.branch_id', $this->branchA->id)
            ->set('form.cost', 2500.50)
            ->call('saveMachine')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('machines', [
            'name' => 'Branch A Printer',
            'branch_id' => $this->branchA->id,
            'cost' => 2500.50,
        ]);
    }

    /**
     * Manager is restricted to their assigned branch.
     */
    public function test_manager_restricted_to_assigned_branch(): void
    {
        $this->actingAs($this->managerA);

        // Create machine should auto-assign manager's branch
        Livewire::test(MachineManagement::class)
            ->call('openCreateModal')
            ->set('form.name', 'Manager Machine')
            ->set('form.cost', 1200.00)
            ->call('saveMachine')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('machines', [
            'name' => 'Manager Machine',
            'branch_id' => $this->branchA->id,
            'cost' => 1200.00,
        ]);
    }

    /**
     * Manager cannot view or edit machines from another branch.
     */
    public function test_manager_cannot_edit_other_branch_machine(): void
    {
        // Machine in Branch B
        $machineB = Machine::create([
            'name' => 'Branch B Machine',
            'branch_id' => $this->branchB->id,
            'cost' => 3000.00,
        ]);

        // Try to access as Manager A (who is at Branch A)
        $this->actingAs($this->managerA);

        Livewire::test(MachineManagement::class)
            ->call('openEditModal', $machineB->id)
            ->assertStatus(403);
    }

    /**
     * Owner can edit and delete any machine.
     */
    public function test_owner_can_edit_and_delete_machines(): void
    {
        $machine = Machine::create([
            'name' => 'Original Name',
            'branch_id' => $this->branchA->id,
            'cost' => 1000.00,
        ]);

        $this->actingAs($this->owner);

        Livewire::test(MachineManagement::class)
            ->call('openEditModal', $machine->id)
            ->set('form.name', 'Updated Name')
            ->set('form.cost', 1100.00)
            ->call('saveMachine')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'name' => 'Updated Name',
            'cost' => 1100.00,
        ]);

        Livewire::test(MachineManagement::class)
            ->call('deleteMachine', $machine->id);

        $this->assertSoftDeleted('machines', [
            'id' => $machine->id,
        ]);
    }
}
