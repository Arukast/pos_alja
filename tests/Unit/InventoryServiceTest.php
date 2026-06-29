<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Machine;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    private string $branchId;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryService;

        $branch = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'HQ Bandung',
        ]);
        $this->branchId = $branch->id;

        $this->category = ProductCategory::create([
            'name' => 'Print Kertas',
            'calculation_archetype' => 'per_piece',
        ]);
    }

    /**
     * Test adding stock updates material stock counters and logs ledger row.
     */
    public function test_add_stock_adjusts_material_and_writes_ledger(): void
    {
        // 1. Seed base material
        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Chromo Paper',
            'product_category_id' => $this->category->id,
            'type' => 'kertas',
        ]);

        MaterialBranchStock::create([
            'id' => (string) Str::uuid(),
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'current_stock' => 100,
            'min_stock' => 10,
        ]);

        // 2. Add stock
        $this->inventoryService->addStock($material, 50.0, 'Purchased new sheets', $this->branchId);

        // 3. Assert material stock counts updated in junction
        $stock = MaterialBranchStock::where('material_id', $material->id)->where('branch_id', $this->branchId)->first();
        $this->assertEquals(150.0, $stock->current_stock); // 100 + 50

        // 4. Assert ledger log written
        $ledger = DB::table('stock_ledgers')->where('material_id', $material->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(100.0, $ledger->initial_stock);
        $this->assertEquals(50.0, $ledger->added_stock);
        $this->assertEquals(150.0, $ledger->remaining_stock);
        $this->assertEquals('Purchased new sheets', $ledger->notes);
    }

    /**
     * Test logging print failure (waste) logs the failure and decrements sisa stock.
     */
    public function test_log_waste_adjusts_material_and_writes_ledger(): void
    {
        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Vinyl Matte Banner',
            'product_category_id' => $this->category->id,
            'type' => 'outdoor',
        ]);

        MaterialBranchStock::create([
            'id' => (string) Str::uuid(),
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'current_stock' => 200,
            'min_stock' => 10,
        ]);

        $designer = User::factory()->create([
            'id' => (string) Str::uuid(),
            'role' => 'designer',
            'branch_id' => $this->branchId,
        ]);
        $operator = User::factory()->create([
            'id' => (string) Str::uuid(),
            'role' => 'production',
            'branch_id' => $this->branchId,
        ]);
        $machine = Machine::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $this->branchId,
            'name' => 'Roland Soljet',
        ]);

        // Log waste
        $this->inventoryService->logWaste([
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
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

        // Assert material stock counts updated in junction
        $stock = MaterialBranchStock::where('material_id', $material->id)->where('branch_id', $this->branchId)->first();
        $this->assertEquals(187.5, $stock->current_stock); // 200 - 12.5

        // Assert ledger log written
        $ledger = DB::table('stock_ledgers')->where('material_id', $material->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals(12.5, $ledger->wasted_stock);
        $this->assertEquals(187.5, $ledger->remaining_stock);
    }
}
