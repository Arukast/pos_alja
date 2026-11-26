<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $designer;

    private User $cashier;

    private User $owner;

    private Customer $customer;

    private Product $product;

    private Material $material;

    private Pricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->designer = $this->user('designer', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->owner = $this->user('owner', null);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->product = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        $this->pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);
    }

    public function test_designer_creates_invoice_with_server_recomputed_prices(): void
    {
        $item = $this->item([
            'unit_price' => 1,
            'cost' => 1,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.grand_total', '600000.00')
            ->assertJsonPath('invoice.orders.0.unit_price', '50000.00')
            ->assertJsonPath('invoice.orders.0.subtotal', '600000.00')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');

        $this->assertStringStartsWith(
            'INV-'.strtoupper((string) $this->branchOne->code).'-',
            $response->json('invoice.invoice_number')
        );

        $this->assertDatabaseHas('orders', [
            'invoice_id' => $response->json('invoice.id'),
            'unit_price' => 50000,
            'subtotal' => 600000,
            'is_manual_price' => false,
        ]);
    }

    public function test_manual_price_requires_values_and_is_audit_logged(): void
    {
        $item = $this->item([
            'is_manual_price' => true,
            'unit_price' => 60000,
            'cost' => 25000,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.subtotal', '720000.00')
            ->assertJsonPath('invoice.orders.0.is_manual_price', true);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'MANUAL_PRICE',
            'model_id' => $response->json('invoice.orders.0.id'),
        ]);

        $invalid = $this->item([
            'is_manual_price' => true,
            'unit_price' => 60000,
            'cost' => null,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$invalid]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_pricing_failure_maps_to_422_instead_of_500(): void
    {
        $otherMaterial = Material::create([
            'name' => 'Tiered Only',
            'product_category_id' => $this->material->product_category_id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        $pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $otherMaterial->id,
            'customer_type' => 'Reguler',
            'min_qty' => 100,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [$this->item(['pricing_id' => $pricing->id, 'quantity' => 2])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_manual_cost_cannot_exceed_price(): void
    {
        $item = $this->item([
            'is_manual_price' => true,
            'unit_price' => 100,
            'cost' => 200,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit_price');
    }

    public function test_cashier_and_owner_cannot_create_invoices(): void
    {
        foreach ([$this->cashier, $this->owner] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/invoices', $this->payload())
                ->assertForbidden();
        }
    }

    public function test_designer_updates_items_on_an_unpaid_dp_invoice(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 100000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'deadline' => now()->addDays(5)->toDateString(),
                'items' => [$this->item([
                    'id' => $orderId,
                    'quantity' => 3,
                    'file_name' => 'banner-revisi.pdf',
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.status', 1)
            ->assertJsonPath('invoice.grand_total', '900000.00')
            ->assertJsonPath('invoice.dp_amount', '100000.00')
            ->assertJsonPath('invoice.accounts_receivable', '800000.00')
            ->assertJsonPath('invoice.orders.0.id', $orderId)
            ->assertJsonPath('invoice.orders.0.file_name', 'banner-revisi.pdf');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'quantity' => 3]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'UPDATE',
            'model_id' => $invoiceId,
        ]);
    }

    /** A designer can append a line and later remove it while keeping the invoice payment history. */
    public function test_designer_can_add_and_remove_invoice_items(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $existingOrderId = (string) $invoiceResponse->json('invoice.orders.0.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 100000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $existingOrderId]), $this->item(['quantity' => 1])],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'invoice.orders')
            ->assertJsonPath('invoice.grand_total', '900000.00')
            ->assertJsonPath('invoice.accounts_receivable', '800000.00');

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['id' => $existingOrderId, 'quantity' => 1])],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'invoice.orders')
            ->assertJsonPath('invoice.grand_total', '300000.00')
            ->assertJsonPath('invoice.accounts_receivable', '200000.00');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('orders', 1);
    }

    /** Item updates require a designer in the invoice branch and return 401 to guests. */
    public function test_invoice_item_update_enforces_auth_and_branch_scope(): void
    {
        $this->putJson('/api/v1/invoices/'.Str::uuid().'/items', [])->assertUnauthorized();

        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $payload = ['items' => [$this->item()]];

        $this->actingAs($this->cashier)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertForbidden();

        $otherDesigner = $this->user('designer', $this->branchTwo);
        $this->actingAs($otherDesigner)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertForbidden();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    /** Fully paid invoices and invoices with an order already in production return 409. */
    public function test_invoice_item_update_is_blocked_after_payment_or_production(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');
        $orderId = (string) $invoiceResponse->json('invoice.orders.0.id');
        $payload = ['items' => [$this->item()]];

        Invoice::query()->whereKey($invoiceId)->update(['status' => 2]);
        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertStatus(409);

        Invoice::query()->whereKey($invoiceId)->update(['status' => 1]);
        Order::query()->whereKey($orderId)->update(['status' => 1]);
        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", $payload)
            ->assertStatus(409);
    }

    /** New totals below previously collected payments are rejected without losing payment history. */
    public function test_invoice_item_update_cannot_make_receivable_negative(): void
    {
        $invoiceResponse = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload())
            ->assertCreated();
        $invoiceId = (string) $invoiceResponse->json('invoice.id');

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$invoiceId}/down-payments", [
                'amount' => 200000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoiceId}/items", [
                'items' => [$this->item(['width' => 0.5, 'length' => 1, 'quantity' => 1])],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals(400000, Invoice::query()->findOrFail($invoiceId)->accounts_receivable);
    }

    public function test_cross_branch_pricing_is_rejected(): void
    {
        $otherPricing = Pricing::create([
            'branch_id' => $this->branchTwo->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $item = $this->item(['pricing_id' => $otherPricing->id]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['items' => [$item]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload(['discount' => 700000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount');
    }

    public function test_invoice_creation_rolls_back_when_a_later_item_fails(): void
    {
        $otherPricing = Pricing::create([
            'branch_id' => $this->branchTwo->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->payload([
                'items' => [
                    $this->item(),
                    $this->item(['pricing_id' => $otherPricing->id]),
                ],
            ]))
            ->assertStatus(422);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_designer_can_create_a_customer_and_cashier_cannot(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/customers', [
                'name' => 'New Customer',
                'phone' => '0812000000',
                'institution' => 'Masyarakat/Retail',
                'type' => 'Reguler',
            ])
            ->assertCreated()
            ->assertJsonPath('customer.name', 'New Customer');

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/customers', [
                'name' => 'Blocked Customer',
            ])
            ->assertForbidden();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'discount' => 0,
            'deadline' => now()->addDay()->toDateString(),
            'source' => 'Offline',
            'items' => [$this->item()],
        ], $overrides);
    }

    private function item(array $overrides = []): array
    {
        return array_merge([
            'pricing_id' => $this->pricing->id,
            'file_name' => 'banner.pdf',
            'notes' => 'Test item',
            'quantity' => 2,
            'width' => 2,
            'length' => 3,
            // Legacy clients may still send machine_id; it must be ignored.
            'machine_id' => (string) Str::uuid(),
            'is_manual_price' => false,
        ], $overrides);
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
}
