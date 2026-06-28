<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\BranchManagement;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'username' => 'testowner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => null,
        ]);

        $this->manager = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'username' => 'testmanager',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => null,
        ]);

        $this->cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'username' => 'testcashier',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => null,
        ]);
    }

    /**
     * Non-owner role is blocked from mounting the BranchManagement component.
     */
    public function test_non_owner_role_cannot_access_branch_management(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(BranchManagement::class)
            ->assertStatus(403);
    }

    /**
     * Non-owner role receives 403 when trying to access the component.
     */
    public function test_non_owner_role_receives_403(): void
    {
        $this->actingAs($this->manager);

        Livewire::test(BranchManagement::class)
            ->assertStatus(403);
    }

    /**
     * Owner can access and render BranchManagement.
     */
    public function test_owner_can_access_branch_management(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->assertStatus(200)
            ->assertSee('Add Branch');
    }

    /**
     * Owner can create a new branch.
     */
    public function test_owner_can_create_branch(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->call('openCreateModal')
            ->set('form.name', 'Jakarta Main')
            ->set('form.code', 'JKT')
            ->set('form.phone', '08123456789')
            ->set('form.address', 'Sudirman St No. 12')
            ->set('form.tax_rate', 11.0)
            ->set('form.receipt_header', 'Welcome to Jakarta Store!')
            ->call('saveBranch')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertSet('showSuccessAlert', true);

        $this->assertDatabaseHas('branches', [
            'name' => 'Jakarta Main',
            'code' => 'JKT',
            'phone' => '08123456789',
            'address' => 'Sudirman St No. 12',
            'is_active' => true,
        ]);

        $branch = Branch::where('code', 'JKT')->first();
        $this->assertEquals(11.0, $branch->settings['tax_rate']);
        $this->assertEquals('Welcome to Jakarta Store!', $branch->settings['receipt_header']);
    }

    /**
     * Validation rules prevent duplicate branch configurations.
     */
    public function test_validation_prevents_duplicate_branch_code(): void
    {
        Branch::create([
            'name' => 'Existing Branch',
            'code' => 'EXS',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->call('openCreateModal')
            ->set('form.name', 'Duplicate Branch')
            ->set('form.code', 'EXS') // Existing code
            ->call('saveBranch')
            ->assertHasErrors(['form.code' => 'unique']);
    }

    /**
     * Owner can edit an existing branch.
     */
    public function test_owner_can_edit_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Old Name',
            'code' => 'OLD',
            'phone' => '123',
            'address' => 'Old Address',
            'is_active' => true,
            'settings' => ['tax_rate' => 5.0, 'receipt_header' => 'Old Header'],
        ]);

        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->call('openEditModal', $branch->id)
            ->assertSet('editingBranchId', $branch->id)
            ->assertSet('form.name', 'Old Name')
            ->set('form.name', 'New Name')
            ->set('form.tax_rate', 10.0)
            ->call('saveBranch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'New Name',
            'code' => 'OLD',
        ]);

        $this->assertEquals(10.0, $branch->fresh()->settings['tax_rate']);
    }

    /**
     * Owner can toggle branch active status.
     */
    public function test_owner_can_toggle_branch_active_status(): void
    {
        $branch = Branch::create([
            'name' => 'Status Branch',
            'code' => 'STT',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->call('toggleBranchStatus', $branch->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $branch->fresh()->is_active);

        Livewire::test(BranchManagement::class)
            ->call('toggleBranchStatus', $branch->id)
            ->assertHasNoErrors();

        $this->assertTrue((bool) $branch->fresh()->is_active);
    }

    /**
     * Owner can delete a branch.
     */
    public function test_owner_can_delete_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Delete Me',
            'code' => 'DLT',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner);

        Livewire::test(BranchManagement::class)
            ->call('deleteBranch', $branch->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($branch);
    }
}
