<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchases_table_has_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('purchases'));
        $this->assertTrue(Schema::hasColumns('purchases', [
            'id',
            'branch_id',
            'user_id',
            'purchased_at',
            'supplier',
            'total_amount',
            'notes',
            'idempotency_key',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_purchases_branch_and_idempotency_key_are_unique_together(): void
    {
        $indexes = collect(Schema::getIndexes('purchases'));

        $unique = $indexes->first(fn (array $index): bool => $index['unique']
            && $index['columns'] === ['branch_id', 'idempotency_key']);

        $this->assertNotNull($unique, 'Expected a unique index on (branch_id, idempotency_key).');
    }

    public function test_purchase_items_table_has_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('purchase_items'));
        $this->assertTrue(Schema::hasColumns('purchase_items', [
            'id',
            'purchase_id',
            'material_id',
            'quantity',
            'unit_cost',
            'subtotal',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_purchase_items_cascade_with_their_purchase(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('purchase_items'));

        $purchaseKey = $foreignKeys->first(fn (array $foreign): bool => $foreign['columns'] === ['purchase_id']);

        $this->assertNotNull($purchaseKey);
        $this->assertSame('purchases', $purchaseKey['foreign_table']);
        $this->assertSame('cascade', strtolower((string) $purchaseKey['on_delete']));
    }

    public function test_materials_table_has_a_unit_column_defaulting_to_pcs(): void
    {
        $this->assertTrue(Schema::hasColumn('materials', 'unit'));

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);

        $materialId = (string) Str::uuid();

        DB::table('materials')->insert([
            'id' => $materialId,
            'product_category_id' => $category->id,
            'name' => 'Unitless Material',
            'cost' => 0,
            'price' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('pcs', DB::table('materials')->where('id', $materialId)->value('unit'));
    }

    public function test_machine_tables_are_dropped_but_pricing_tables_remain(): void
    {
        $this->assertFalse(Schema::hasTable('machines'));
        $this->assertFalse(Schema::hasTable('machine_pricing'));
        $this->assertTrue(Schema::hasTable('pricing'));
        $this->assertTrue(Schema::hasTable('attribute_pricing'));
    }

    public function test_wasted_materials_has_no_machine_column_but_keeps_order_link(): void
    {
        $this->assertTrue(Schema::hasTable('wasted_materials'));
        $this->assertTrue(Schema::hasColumn('wasted_materials', 'order_id'));
        $this->assertFalse(Schema::hasColumn('wasted_materials', 'machine_id'));
    }

    public function test_stock_ledgers_has_traceability_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('stock_ledgers', [
            'type',
            'reason',
            'user_id',
            'reference_type',
            'reference_id',
        ]));
    }

    public function test_stock_ledgers_reference_columns_are_indexed_together(): void
    {
        $indexes = collect(Schema::getIndexes('stock_ledgers'));

        $composite = $indexes->first(fn (array $index): bool => $index['columns'] === ['reference_type', 'reference_id']);

        $this->assertNotNull($composite, 'Expected an index on (reference_type, reference_id).');
    }
}
