<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\Material;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    private string $branchId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService;

        // Seed branch
        $branch = Branch::create([
            'name' => 'Main Branch',
        ]);
        $this->branchId = $branch->id;
    }

    /**
     * Test adding stock updates material stock counters and logs ledger row.
     */
    public function test_add_stock_adjusts_material_and_writes_ledger(): void
    {
        // 1. Seed base material
        $material = Material::create([
            'branch_id' => $this->branchId,
            'name' => 'Chromo Paper',
            'category' => 'lembar',
            'type' => 'kertas',
            'initial_stock' => 100,
            'added_stock' => 0,
            'current_stock' => 100,
            'sold_stock' => 10,
            'wasted_stock' => 5,
            'remaining_stock' => 85, // 100 - 10 - 5
        ]);

        // 2. Add stock
        $this->inventoryService->addStock($material, 50.0, 'Purchased new sheets');

        // 3. Assert material stock counts updated
        $updatedMaterial = Material::find($material->id);
        $this->assertEquals(85.0, $updatedMaterial->initial_stock); // sisa stock before
        $this->assertEquals(50.0, $updatedMaterial->added_stock);
        $this->assertEquals(135.0, $updatedMaterial->current_stock); // 85 + 50
        $this->assertEquals(120.0, $updatedMaterial->remaining_stock); // 135 - 10 - 5

        // 4. Assert ledger log written
        $ledger = DB::table('stock_ledgers')->where('material_id', $material->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(85.0, $ledger->initial_stock);
        $this->assertEquals(50.0, $ledger->added_stock);
        $this->assertEquals(120.0, $ledger->remaining_stock);
        $this->assertEquals('Purchased new sheets', $ledger->notes);
    }

    /**
     * Test logging print failure (waste) logs the failure and decrements sisa stock.
     */
    public function test_log_waste_adjusts_material_and_writes_ledger(): void
    {
        $material = Material::create([
            'branch_id' => $this->branchId,
            'name' => 'Vinyl Matte Banner',
            'category' => 'meter',
            'type' => 'outdoor',
            'initial_stock' => 200,
            'added_stock' => 0,
            'current_stock' => 200,
            'sold_stock' => 40,
            'wasted_stock' => 10,
            'remaining_stock' => 150,
        ]);

        $designer = User::factory()->create(['role' => 'designer']);
        $operator = User::factory()->create(['role' => 'production']);
        $machine = Machine::create([
            'branch_id' => $this->branchId,
            'name' => 'Roland Soljet',
        ]);

        // Log waste
        $this->inventoryService->logWaste([
            'material_id' => $material->id,
            'quantity' => 12.5,
            'machine_id' => $machine->id,
            'designer_id' => $designer->id,
            'operator_id' => $operator->id,
            'notes' => 'Tinta luntur',
        ]);

        // Assert wasted log created
        $waste = DB::table('wasted_materials')->where('material_id', $material->id)->first();
        $this->assertNotNull($waste);
        $this->assertEquals(12.5, $waste->quantity);
        $this->assertEquals('Tinta luntur', $waste->notes);
        $this->assertEquals($designer->id, $waste->designer_id);
        $this->assertEquals($operator->id, $waste->operator_id);
        $this->assertEquals($machine->id, $waste->machine_id);

        // Assert material stock counts updated
        $updatedMaterial = Material::find($material->id);
        $this->assertEquals(22.5, $updatedMaterial->wasted_stock); // 10 + 12.5
        $this->assertEquals(137.5, $updatedMaterial->remaining_stock); // 200 - 40 - 22.5

        // Assert ledger log written
        $ledger = DB::table('stock_ledgers')->where('material_id', $material->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(22.5, $ledger->wasted_stock);
        $this->assertEquals(137.5, $ledger->remaining_stock);
    }
}
