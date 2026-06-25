<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Dashboards\Cashier;
use App\Livewire\Dashboards\Manager;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed branch
        Branch::create([
            'id' => 1,
            'name' => 'Main Branch',
        ]);
    }

    /**
     * Test Cashier can log expenses.
     */
    public function test_cashier_can_log_expenses(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        Livewire::actingAs($user)
            ->test(Cashier::class)
            ->set('amount', 50000)
            ->set('type', 'laba')
            ->set('description', 'Office cleaning supplies')
            ->call('logExpense')
            ->assertSet('amount', '')
            ->assertSet('description', '')
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
        $user = User::factory()->create(['role' => 'manager']);
        
        $material = Material::create([
            'branch_id' => 1,
            'name' => 'Flexy 340gr',
            'category' => 'meter',
            'type' => 'outdoor',
            'initial_stock' => 100,
            'added_stock' => 0,
            'current_stock' => 100,
            'sold_stock' => 0,
            'wasted_stock' => 0,
            'remaining_stock' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(Manager::class)
            ->set('selectedMaterialId', $material->id)
            ->set('addStockQuantity', 50)
            ->set('addStockNotes', 'Restocked 50m')
            ->call('addMaterialStock')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'remaining_stock' => 150,
        ]);
        
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'added_stock' => 50.00,
            'notes' => 'Restocked 50m',
        ]);
    }
}
