<?php

namespace Tests\Feature;

use App\Livewire\Dashboards\Cashier;
use App\Livewire\Dashboards\Manager;
use App\Models\Branch;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed branch
        $this->branch = Branch::create([
            'name' => 'Main Branch',
        ]);
    }

    /**
     * Test Cashier can log expenses.
     */
    public function test_cashier_can_log_expenses(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);

        Livewire::actingAs($user)
            ->test(Cashier::class)
            ->set('expenseForm.amount', 50000)
            ->set('expenseForm.type', 'laba')
            ->set('expenseForm.description', 'Office cleaning supplies')
            ->call('logExpense')
            ->assertSet('expenseForm.amount', '')
            ->assertSet('expenseForm.description', '')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'amount' => 50000,
            'type' => 'laba',
            'description' => 'Office cleaning supplies',
            'cashier_name' => $user->name,
        ]);
    }

    /**
     * Test Manager can restock materials.
     */
    public function test_manager_can_restock_materials(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);

        $material = Material::create([
            'branch_id' => $this->branch->id,
            'name' => 'Flexy 340gr',
            'category' => 'meter',
            'type' => 'outdoor',
            'current_stock' => 100,
            'min_stock' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('restockForm.selectedMaterialId', $material->id)
            ->set('restockForm.addStockQuantity', 50)
            ->set('restockForm.addStockNotes', 'Restocked 50m')
            ->call('addMaterialStock')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'current_stock' => 150,
        ]);

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'added_stock' => 50.00,
            'notes' => 'Restocked 50m',
        ]);
    }
}
