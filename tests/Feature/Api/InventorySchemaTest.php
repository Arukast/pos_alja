<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
