<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
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

        // Log waste
        $this->inventoryService->logWaste([
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'quantity' => 12.5,
            'designer_id' => $designer->id,
            'operator_id' => $operator->id,
            'actor' => $operator,
            'notes' => 'Tinta luntur',
        ]);

        // Assert wasted log created
        $waste = DB::table('wasted_materials')->where('material_id', $material->id)->first();
        $this->assertNotNull($waste);
        $this->assertEquals(12.5, $waste->quantity);
        $this->assertEquals('Tinta luntur', $waste->notes);
        $this->assertEquals($designer->id, $waste->designer_id);
        $this->assertEquals($operator->id, $waste->operator_id);
        $this->assertFalse(property_exists($waste, 'machine_id'));

        // Assert material stock counts updated in junction
        $stock = MaterialBranchStock::where('material_id', $material->id)->where('branch_id', $this->branchId)->first();
        $this->assertEquals(187.5, $stock->current_stock); // 200 - 12.5

        // Assert ledger log written
        $ledger = DB::table('stock_ledgers')->where('material_id', $material->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals('waste', $ledger->type);
        $this->assertEquals($operator->id, $ledger->user_id);
        $this->assertEquals(12.5, $ledger->wasted_stock);
        $this->assertEquals(187.5, $ledger->remaining_stock);
    }

    public function test_waste_may_push_stock_negative(): void
    {
        $material = $this->material();
        $this->seedStock($material, 1.0);
        $operator = User::factory()->create(['role' => 'production', 'branch_id' => $this->branchId]);

        $this->inventoryService->logWaste([
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'quantity' => 4.0,
            'actor' => $operator,
        ]);

        $this->assertSame(-3.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'waste',
            'user_id' => $operator->id,
            'wasted_stock' => 4,
            'remaining_stock' => -3,
        ]);
    }

    public function test_apply_movement_creates_missing_stock_row_with_zero_minimum(): void
    {
        $material = $this->material();

        $result = $this->inventoryService->applyMovement(
            $material->id,
            $this->branchId,
            'restock',
            5.0,
            notes: 'Restock awal',
        );

        $stock = $this->stockRow($material);
        $this->assertSame(5.0, (float) $stock->current_stock);
        $this->assertSame(0.0, (float) $stock->min_stock);
        $this->assertSame(0.0, $result['old_stock']);
        $this->assertSame(5.0, $result['new_stock']);

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'restock',
            'added_stock' => 5,
            'remaining_stock' => 5,
        ]);
    }

    public function test_usage_may_push_stock_negative(): void
    {
        $material = $this->material();
        $this->seedStock($material, 5.0);

        $result = $this->inventoryService->applyMovement($material->id, $this->branchId, 'usage', 8.0);

        $this->assertSame(-3.0, $result['new_stock']);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'usage',
            'used_stock' => 8,
            'remaining_stock' => -3,
        ]);
    }

    public function test_adjustment_absolute_sets_target_and_writes_signed_delta(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);

        $result = $this->inventoryService->applyMovement(
            $material->id,
            $this->branchId,
            'adjustment',
            7.0,
            reason: 'Stock opname',
            absolute: true,
        );

        $this->assertSame(10.0, $result['old_stock']);
        $this->assertSame(7.0, $result['new_stock']);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'adjustment',
            'reason' => 'Stock opname',
            'initial_stock' => 10,
            'added_stock' => -3,
            'remaining_stock' => 7,
        ]);
    }

    public function test_adjustment_delta_applies_signed_change(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);

        $this->inventoryService->applyMovement($material->id, $this->branchId, 'adjustment', -4.0);

        $this->assertSame(6.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'adjustment',
            'added_stock' => -4,
            'remaining_stock' => 6,
        ]);
    }

    public function test_adjustment_target_below_zero_is_rejected_with_mode_key(): void
    {
        $material = $this->material();
        $this->seedStock($material, 2.0);

        try {
            $this->inventoryService->applyMovement($material->id, $this->branchId, 'adjustment', -3.0);
            $this->fail('Negative delta target was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('delta', $e->errors());
        }

        try {
            $this->inventoryService->applyMovement($material->id, $this->branchId, 'adjustment', -1.0, absolute: true);
            $this->fail('Negative absolute target was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('new_quantity', $e->errors());
        }

        $this->assertSame(2.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_negative_movement_below_floor_is_rejected_with_quantity_key(): void
    {
        $material = $this->material();
        $this->seedStock($material, 0.0);

        foreach (['usage', 'waste'] as $type) {
            try {
                $this->inventoryService->applyMovement($material->id, $this->branchId, $type, 100000000.0);
                $this->fail("Over-floor {$type} was accepted.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('quantity', $e->errors());
            }
        }

        $this->assertSame(0.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_positive_movement_above_ceiling_is_rejected_with_quantity_key(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);

        try {
            $this->inventoryService->applyMovement($material->id, $this->branchId, 'restock', 100000000.0);
            $this->fail('Over-ceiling restock was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }

        $this->assertSame(10.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_adjustment_above_ceiling_is_rejected_with_mode_key(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);

        try {
            $this->inventoryService->applyMovement($material->id, $this->branchId, 'adjustment', 100000000.0, absolute: true);
            $this->fail('Over-ceiling absolute target was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('new_quantity', $e->errors());
        }

        try {
            $this->inventoryService->applyMovement($material->id, $this->branchId, 'adjustment', 100000000.0);
            $this->fail('Over-ceiling delta target was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('delta', $e->errors());
        }

        $this->assertSame(10.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_purchase_movement_writes_reference_and_actor(): void
    {
        $material = $this->material();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $this->branchId]);
        $purchaseId = (string) Str::uuid();

        $this->inventoryService->applyMovement(
            $material->id,
            $this->branchId,
            'purchase',
            3.0,
            referenceType: 'purchase',
            referenceId: $purchaseId,
            actor: $manager,
        );

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'purchase',
            'reference_type' => 'purchase',
            'reference_id' => $purchaseId,
            'user_id' => $manager->id,
            'added_stock' => 3,
        ]);
    }

    public function test_restock_and_waste_ledgers_carry_type_and_actor(): void
    {
        $material = $this->material();
        $this->seedStock($material, 100.0);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $this->branchId]);
        $operator = User::factory()->create(['role' => 'production', 'branch_id' => $this->branchId]);
        $this->actingAs($manager);

        $this->inventoryService->addStock($material, 5.0, 'Restock', $this->branchId);

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'restock',
            'user_id' => $manager->id,
            'added_stock' => 5,
        ]);

        $this->inventoryService->logWaste([
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'quantity' => 2.0,
            'actor' => $operator,
            'notes' => 'Rusak',
        ]);

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'waste',
            'user_id' => $operator->id,
            'wasted_stock' => 2,
            'remaining_stock' => 103,
        ]);
    }

    public function test_print_deduction_writes_usage_ledger_with_order_reference_and_actor(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);
        $operator = User::factory()->create(['role' => 'production', 'branch_id' => $this->branchId]);
        $orderId = (string) Str::uuid();

        $returned = $this->inventoryService->deductStockOnPrint($material->id, 4.0, $this->branchId, $orderId, $operator);

        $this->assertInstanceOf(StockLedger::class, $returned);
        $this->assertSame(10.0, (float) $returned->initial_stock);
        $this->assertSame(6.0, (float) $returned->remaining_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'usage',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'user_id' => $operator->id,
            'used_stock' => 4,
            'remaining_stock' => 6,
        ]);
    }

    public function test_print_deduction_may_push_stock_negative(): void
    {
        $material = $this->material();
        $this->seedStock($material, 2.0);
        $operator = User::factory()->create(['role' => 'production', 'branch_id' => $this->branchId]);
        $orderId = (string) Str::uuid();

        $this->inventoryService->deductStockOnPrint($material->id, 5.0, $this->branchId, $orderId, $operator);

        $this->assertSame(-3.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'usage',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'user_id' => $operator->id,
            'used_stock' => 5,
            'remaining_stock' => -3,
        ]);
    }

    public function test_initialize_branch_stock_keeps_min_stock_and_writes_initial_ledger(): void
    {
        $material = $this->material();

        $this->inventoryService->initializeBranchStock($material->id, $this->branchId, 10.0, 2.0);

        $stock = $this->stockRow($material);
        $this->assertSame(10.0, (float) $stock->current_stock);
        $this->assertSame(2.0, (float) $stock->min_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'initial',
            'initial_stock' => 0,
            'added_stock' => 10,
            'remaining_stock' => 10,
        ]);
    }

    public function test_initialize_branch_stock_does_not_apply_initial_stock_twice(): void
    {
        $material = $this->material();

        $this->inventoryService->initializeBranchStock($material->id, $this->branchId, 10.0, 2.0);
        $this->inventoryService->initializeBranchStock($material->id, $this->branchId, 10.0, 2.0);

        $stock = $this->stockRow($material);
        $this->assertSame(10.0, (float) $stock->current_stock);
        $this->assertSame(2.0, (float) $stock->min_stock);
        $this->assertSame(1, DB::table('stock_ledgers')
            ->where('material_id', $material->id)
            ->where('type', 'initial')
            ->count());
        $this->assertDatabaseCount('stock_ledgers', 1);
    }

    public function test_usage_reversal_increases_stock_and_records_added_stock(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);
        $orderId = (string) Str::uuid();

        $this->inventoryService->applyMovement(
            $material->id,
            $this->branchId,
            'usage',
            6.0,
            referenceType: 'order',
            referenceId: $orderId,
        );

        $result = $this->inventoryService->applyMovement(
            $material->id,
            $this->branchId,
            'usage_reversal',
            6.0,
            referenceType: 'order',
            referenceId: $orderId,
        );

        $this->assertSame(4.0, $result['old_stock']);
        $this->assertSame(10.0, $result['new_stock']);
        $this->assertSame(10.0, (float) $this->stockRow($material)->current_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $material->id,
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'added_stock' => 6,
            'used_stock' => 0,
            'wasted_stock' => 0,
            'remaining_stock' => 10,
        ]);
    }

    public function test_apply_movement_returns_ledger_id(): void
    {
        $material = $this->material();
        $this->seedStock($material, 10.0);

        $result = $this->inventoryService->applyMovement($material->id, $this->branchId, 'usage', 4.0);

        $ledgerId = DB::table('stock_ledgers')
            ->where('material_id', $material->id)
            ->where('type', 'usage')
            ->value('id');

        $this->assertNotNull($ledgerId);
        $this->assertSame($ledgerId, $result['ledger_id']);
    }

    public function test_apply_movement_rejects_unknown_type(): void
    {
        $material = $this->material();

        $this->expectException(InvalidArgumentException::class);

        $this->inventoryService->applyMovement($material->id, $this->branchId, 'transfer', 1.0);
    }

    private function material(string $name = 'Sticker Vinyl'): Material
    {
        return Material::create([
            'name' => $name,
            'product_category_id' => $this->category->id,
            'type' => 'roll',
        ]);
    }

    private function seedStock(Material $material, float $currentStock): void
    {
        MaterialBranchStock::create([
            'material_id' => $material->id,
            'branch_id' => $this->branchId,
            'current_stock' => $currentStock,
            'min_stock' => 1,
        ]);
    }

    private function stockRow(Material $material): MaterialBranchStock
    {
        return MaterialBranchStock::where('material_id', $material->id)
            ->where('branch_id', $this->branchId)
            ->firstOrFail();
    }
}
