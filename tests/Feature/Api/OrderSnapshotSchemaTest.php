<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderSnapshotSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_snapshot_columns_exist_with_expected_types(): void
    {
        foreach (['product_id', 'material_id', 'customer_type', 'reseller_discount_percent',
            'quantity_discount_percent', 'discount_amount'] as $column) {
            $this->assertTrue(Schema::hasColumn('orders', $column), $column);
        }

        // SQLite and PostgreSQL report varchar; MySQL reports string for a Laravel string column.
        $this->assertContains(Schema::getColumnType('orders', 'customer_type'), ['string', 'varchar']);

        $customer = Customer::create(['name' => 'Snapshot Customer']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-SNAPSHOT-1',
            'created_date' => now(),
            'customer_id' => $customer->id,
        ]);
        $order = Order::create([
            'invoice_id' => $invoice->id,
            'ordered_at' => now(),
        ]);

        $this->assertSame('Reguler', $order->refresh()->customer_type);
    }

    public function test_legacy_pricing_identity_is_gone(): void
    {
        $this->assertFalse(Schema::hasColumn('orders', 'pricing_id'));
        $this->assertFalse(Schema::hasTable('pricing'));
        $this->assertFalse(Schema::hasTable('attribute_pricing'));
    }

    public function test_product_quantity_discounts_table_shape(): void
    {
        $this->assertTrue(Schema::hasTable('product_quantity_discounts'));
        foreach (['id', 'product_id', 'min_qty', 'discount_percent', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('product_quantity_discounts', $column), $column);
        }
        $indexes = collect(Schema::getIndexes('product_quantity_discounts'));
        $this->assertTrue($indexes->contains(fn (array $i): bool => $i['name'] === 'product_quantity_discounts_product_id_min_qty_unique'));
    }

    public function test_order_model_fillable_and_relations_use_snapshots(): void
    {
        $order = new Order;
        foreach (['product_id', 'material_id', 'customer_type', 'reseller_discount_percent',
            'quantity_discount_percent', 'discount_amount'] as $column) {
            $this->assertContains($column, $order->getFillable());
        }
        $this->assertNotContains('pricing_id', $order->getFillable());
        $this->assertInstanceOf(BelongsTo::class, $order->product());
        $this->assertInstanceOf(BelongsTo::class, $order->material());
    }
}
