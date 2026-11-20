<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $production;

    private User $cashier;

    private User $designer;

    private Invoice $invoice;

    private Invoice $otherInvoice;

    private Material $material;

    private Machine $machine;

    private Pricing $pricing;

    private Order $order;

    private Order $otherOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->production = $this->user('production', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->designer = $this->user('designer', $this->branchOne);

        $customer = Customer::create([
            'name' => 'Jane Customer',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);

        $product = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 10000,
            'price' => 15000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 1,
        ]);

        $this->machine = Machine::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Printer A',
            'cost' => 1000,
        ]);

        $this->pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 10000,
            'profit' => 40000,
        ]);

        $this->invoice = $this->invoice($this->branchOne, $customer, 'INV-B1-001');
        $this->otherInvoice = $this->invoice($this->branchTwo, $customer, 'INV-B2-001');

        $this->order = $this->order($this->invoice, $this->pricing, 3, 2);
        $this->otherOrder = $this->order($this->otherInvoice, $this->pricing, 1, 1);
    }

    public function test_start_deducts_area_stock_and_is_idempotent(): void
    {
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/start")
            ->assertOk()
            ->assertJsonPath('order.status', 1);

        $this->assertSame(4.0, (float) $this->material->stocks()->first()?->current_stock);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->material->id,
            'used_stock' => 6,
        ]);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/start")
            ->assertStatus(422);

        $this->assertSame(4.0, (float) $this->material->stocks()->first()?->current_stock);
    }

    public function test_complete_requires_processing_and_sets_completed(): void
    {
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/complete")
            ->assertStatus(422);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/start")
            ->assertOk();

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/complete")
            ->assertOk()
            ->assertJsonPath('order.status', 2);
    }

    public function test_production_cannot_start_another_branch_order(): void
    {
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->otherOrder->id}/start")
            ->assertForbidden();
    }

    public function test_non_production_cannot_start_orders(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/orders/{$this->order->id}/start")
            ->assertForbidden();
    }

    public function test_waste_deducts_stock_and_writes_ledger(): void
    {
        $response = $this->actingAs($this->production)
            ->postJson('/api/v1/waste', [
                'material_id' => $this->material->id,
                'machine_id' => $this->machine->id,
                'designer_id' => $this->designer->id,
                'quantity' => 2,
                'dimensions' => '1.2m',
                'notes' => 'Failed print',
            ])
            ->assertCreated()
            ->assertJsonMissingPath('waste.cost');

        $this->assertEquals(2, (float) $response->json('waste.quantity'));

        $this->assertSame(8.0, (float) $this->material->stocks()->first()?->current_stock);
        $this->assertDatabaseHas('wasted_materials', [
            'id' => $response->json('waste.id'),
            'branch_id' => $this->branchOne->id,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->material->id,
            'wasted_stock' => 2,
        ]);
    }

    public function test_waste_rejects_material_without_branch_stock(): void
    {
        $otherMaterial = Material::create([
            'name' => 'Other Roll',
            'product_category_id' => $this->material->product_category_id,
            'type' => 'roll',
        ]);

        $this->actingAs($this->production)
            ->postJson('/api/v1/waste', [
                'material_id' => $otherMaterial->id,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_waste_requires_a_positive_quantity(): void
    {
        $this->actingAs($this->production)
            ->postJson('/api/v1/waste', [
                'material_id' => $this->material->id,
                'quantity' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }

    private function invoice(Branch $branch, Customer $customer, string $number): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'designer_id' => $this->designer->id,
            'status' => 1,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 100000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ]);
    }

    private function order(Invoice $invoice, Pricing $pricing, float $area, float $quantity): Order
    {
        return Order::create([
            'invoice_id' => $invoice->id,
            'pricing_id' => $pricing->id,
            'file_name' => 'job.pdf',
            'notes' => 'Production job',
            'area' => $area,
            'quantity' => $quantity,
            'unit_price' => 50000,
            'subtotal' => 100000,
            'cost' => 60000,
            'profit' => 40000,
            'ordered_at' => now()->toDateString(),
            'status' => 0,
            'pickup_status' => 0,
            'is_manual_price' => false,
        ]);
    }
}
