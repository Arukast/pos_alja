<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Enforces product/material identity rules for orders that do not use a material.
 */
class NoMaterialOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $customer;

    private Product $materialProduct;

    private Product $noMaterialProduct;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'No Material Branch',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->customer = Customer::create([
            'name' => 'No Material Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'No Material Category',
        ]);

        $this->materialProduct = Product::create([
            'name' => 'Banner',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
            'requires_material' => true,
            'uses_attributes' => false,
        ]);

        $this->noMaterialProduct = Product::create([
            'name' => 'Jasa Desain',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
            'requires_material' => false,
            'uses_attributes' => false,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 5000,
            'price' => 10000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
            'min_stock' => 1,
        ]);
    }

    public function test_no_material_quote_skips_material_availability(): void
    {
        $this->material->update(['is_active' => false]);
        MaterialBranchStock::query()->delete();

        $this->actingAs($this->designer())
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->noMaterialProduct->id,
                'material_id' => null,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '0.00')
            ->assertJsonPath('quote.subtotal', '0.00')
            ->assertJsonMissingPath('quote.cost');
    }

    public function test_no_material_quote_rejects_a_client_material(): void
    {
        $this->actingAs($this->designer())
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->noMaterialProduct->id,
                'material_id' => $this->material->id,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_material_product_quote_requires_a_material(): void
    {
        $this->actingAs($this->designer())
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->materialProduct->id,
                'material_id' => null,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_no_material_invoice_stores_null_material_identity_and_skips_stock(): void
    {
        $this->materialProduct->materials()->sync([$this->material->id]);
        $this->material->update(['is_active' => false]);
        MaterialBranchStock::query()->delete();

        $response = $this->actingAs($this->designer())
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.material_id', null)
            ->assertJsonPath('invoice.orders.0.subtotal', '0.00');

        $this->assertDatabaseHas('orders', [
            'invoice_id' => $response->json('invoice.id'),
            'product_id' => $this->noMaterialProduct->id,
            'material_id' => null,
        ]);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_invoice_show_renders_a_null_material_line(): void
    {
        $created = $this->actingAs($this->designer())
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated();

        $this->actingAs($this->designer())
            ->getJson('/api/v1/invoices/'.$created->json('invoice.id'))
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.material', null)
            ->assertJsonMissingPath('invoice.orders.0.pricing');
    }

    public function test_no_material_invoice_edit_recalculates_an_unchanged_line(): void
    {
        $created = $this->actingAs($this->designer())
            ->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated();

        $this->actingAs($this->designer())
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->invoiceItem([
                    'id' => $created->json('invoice.orders.0.id'),
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.material_id', null)
            ->assertJsonPath('invoice.orders.0.subtotal', '0.00');
    }

    /** @return array<string, mixed> */
    private function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'discount' => 0,
            'items' => [$this->invoiceItem()],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function invoiceItem(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->noMaterialProduct->id,
            'material_id' => null,
            'file_name' => 'desain.pdf',
            'notes' => 'Tanpa bahan',
            'quantity' => 2,
            'is_manual_price' => false,
        ], $overrides);
    }

    private function designer(): User
    {
        return User::factory()->create([
            'role' => 'designer',
            'branch_id' => $this->branch->id,
        ]);
    }
}
