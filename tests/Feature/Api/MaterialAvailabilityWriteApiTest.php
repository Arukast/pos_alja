<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Enforces global and branch material availability on quote and invoice writes.
 */
class MaterialAvailabilityWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $designer;

    private Customer $customer;

    private Product $product;

    private Material $material;

    private MaterialBranchStock $stock;

    private Pricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->designer = User::factory()->create([
            'role' => 'designer',
            'branch_id' => $this->branch->id,
        ]);

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

        $this->stock = MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
            'min_stock' => 1,
        ]);

        $this->pricing = Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);
    }

    public function test_quote_rejects_globally_inactive_material(): void
    {
        $this->material->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_quote_rejects_branch_inactive_material(): void
    {
        $this->stock->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_quote_rejects_material_without_branch_stock(): void
    {
        $material = Material::create([
            'name' => 'No Stock',
            'product_category_id' => $this->product->product_category_id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', $this->quotePayload(['material_id' => $material->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_quote_still_accepts_an_active_material(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', $this->quotePayload())
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '50000.00')
            ->assertJsonPath('quote.subtotal', '600000.00');
    }

    public function test_invoice_creation_rejects_globally_inactive_material(): void
    {
        $this->material->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_creation_rejects_branch_inactive_material(): void
    {
        $this->stock->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_creation_rejects_material_without_branch_stock(): void
    {
        $pricing = $this->pricingFor($this->materialWithoutStock());

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload([
                'items' => [$this->item(['pricing_id' => $pricing->id])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_creation_rejects_soft_deleted_material(): void
    {
        $this->material->delete();

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_creation_rejects_manual_price_line_with_unavailable_material(): void
    {
        $this->material->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload([
                'items' => [$this->item([
                    'is_manual_price' => true,
                    'unit_price' => 60000,
                    'cost' => 25000,
                ])],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');
    }

    public function test_invoice_edit_allows_an_already_used_inactive_material(): void
    {
        $invoice = $this->createInvoice();

        $this->material->update(['is_active' => false]);
        $this->stock->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item(['id' => $invoice['order_id']])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.id', $invoice['order_id']);
    }

    public function test_invoice_edit_allows_a_new_line_with_an_already_used_inactive_material(): void
    {
        $invoice = $this->createInvoice();

        $this->material->update(['is_active' => false]);
        $this->stock->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [
                    $this->item(['id' => $invoice['order_id']]),
                    $this->item(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'invoice.orders');

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_invoice_edit_rejects_a_different_inactive_material(): void
    {
        $invoice = $this->createInvoice();
        $otherMaterial = $this->materialWithoutStock();
        $otherPricing = $this->pricingFor($otherMaterial);

        MaterialBranchStock::create([
            'material_id' => $otherMaterial->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
            'min_stock' => 1,
        ]);

        $otherMaterial->update(['is_active' => false]);
        MaterialBranchStock::query()
            ->where('material_id', $otherMaterial->id)
            ->where('branch_id', $this->branch->id)
            ->update(['is_active' => false]);

        $beforeGrandTotal = (string) Invoice::query()->findOrFail($invoice['id'])->grand_total;

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [$this->item([
                    'id' => $invoice['order_id'],
                    'pricing_id' => $otherPricing->id,
                ])],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.pricing_id');

        $this->assertSame($beforeGrandTotal, (string) Invoice::query()->findOrFail($invoice['id'])->grand_total);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'id' => $invoice['order_id'],
            'pricing_id' => $this->pricing->id,
        ]);
    }

    public function test_invoice_edit_does_not_accept_a_client_allowance_flag(): void
    {
        $invoice = $this->createInvoice();
        $otherMaterial = $this->materialWithoutStock();
        $otherPricing = $this->pricingFor($otherMaterial);

        MaterialBranchStock::create([
            'material_id' => $otherMaterial->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
            'min_stock' => 1,
            'is_active' => false,
        ]);

        $otherMaterial->update(['is_active' => false]);

        $this->actingAs($this->designer)
            ->putJson("/api/v1/invoices/{$invoice['id']}/items", [
                'items' => [
                    $this->item(['id' => $invoice['order_id']]),
                    $this->item([
                        'pricing_id' => $otherPricing->id,
                        'allow_inactive_material' => true,
                    ]),
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.1.pricing_id');

        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Creates an invoice with the default material and returns its id plus the first order id.
     *
     * @return array{id: string, order_id: string}
     */
    private function createInvoice(): array
    {
        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated();

        return [
            'id' => (string) $response->json('invoice.id'),
            'order_id' => (string) $response->json('invoice.orders.0.id'),
        ];
    }

    private function materialWithoutStock(): Material
    {
        return Material::create([
            'name' => 'No Stock',
            'product_category_id' => $this->product->product_category_id,
            'type' => 'roll',
            'cost' => 20000,
            'price' => 25000,
        ]);
    }

    private function pricingFor(Material $material): Pricing
    {
        return Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function quotePayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'quantity' => 2,
            'width' => 2,
            'length' => 3,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'items' => [$this->item()],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function item(array $overrides = []): array
    {
        return array_merge([
            'pricing_id' => $this->pricing->id,
            'file_name' => 'banner.pdf',
            'notes' => 'Test item',
            'quantity' => 2,
            'width' => 2,
            'length' => 3,
            'is_manual_price' => false,
        ], $overrides);
    }
}
