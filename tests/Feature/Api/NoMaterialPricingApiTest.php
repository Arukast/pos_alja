<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class NoMaterialPricingApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $owner;

    private User $manager;

    private User $designer;

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

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branch);
        $this->designer = $this->user('designer', $this->branch);

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
            'pricing_type' => 'unit',
            'product_category_id' => $category->id,
            'requires_material' => true,
            'uses_attributes' => false,
        ]);

        $this->noMaterialProduct = Product::create([
            'name' => 'Jasa Desain',
            'pricing_type' => 'unit',
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

    public function test_pricing_material_is_nullable_and_keeps_its_foreign_key(): void
    {
        $column = collect(Schema::getColumns('pricing'))->firstWhere('name', 'material_id');
        $this->assertNotNull($column);
        $this->assertTrue($column['nullable']);

        $foreignKey = collect(Schema::getForeignKeys('pricing'))
            ->first(fn (array $fk): bool => $fk['columns'] === ['material_id']);

        $this->assertNotNull($foreignKey);
        $this->assertSame('materials', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertSame('cascade', $foreignKey['on_delete']);
    }

    public function test_owner_creates_no_material_pricing_row(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', $this->pricingPayload([
                'product_id' => $this->noMaterialProduct->id,
                'material_id' => null,
            ]))
            ->assertCreated()
            ->assertJsonPath('pricing.material', null);

        $this->assertDatabaseHas('pricing', [
            'id' => $response->json('pricing.id'),
            'material_id' => null,
        ]);
    }

    public function test_owner_creates_no_material_pricing_when_material_id_key_is_omitted(): void
    {
        $payload = $this->pricingPayload([
            'product_id' => $this->noMaterialProduct->id,
            'material_id' => null,
        ]);
        unset($payload['material_id']);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', $payload)
            ->assertCreated()
            ->assertJsonPath('pricing.material', null);

        $this->assertDatabaseHas('pricing', [
            'id' => $response->json('pricing.id'),
            'material_id' => null,
        ]);
    }

    public function test_pricing_create_rejects_omitted_material_key_for_material_product(): void
    {
        $payload = $this->pricingPayload([
            'product_id' => $this->materialProduct->id,
        ]);
        unset($payload['material_id']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_pricing_create_rejects_null_material_for_material_product(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', $this->pricingPayload([
                'product_id' => $this->materialProduct->id,
                'material_id' => null,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_pricing_create_rejects_material_for_no_material_product(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', $this->pricingPayload([
                'product_id' => $this->noMaterialProduct->id,
                'material_id' => $this->material->id,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_pricing_update_ignores_product_material_and_customer_changes(): void
    {
        $pricing = $this->makeNoMaterialPricing();

        $this->actingAs($this->manager)
            ->putJson("/api/v1/pricing/{$pricing->id}", [
                'product_id' => $this->materialProduct->id,
                'material_id' => $this->material->id,
                'customer_type' => 'Reseller',
                'min_qty' => 2,
                'price' => 30000,
                'cost' => 12000,
            ])
            ->assertOk()
            ->assertJsonPath('pricing.price', '30000.00');

        $this->assertDatabaseHas('pricing', [
            'id' => $pricing->id,
            'product_id' => $this->noMaterialProduct->id,
            'material_id' => null,
            'customer_type' => 'Reguler',
            'min_qty' => 2,
        ]);
    }

    public function test_pricing_index_lists_a_null_material_row(): void
    {
        $pricing = $this->makeNoMaterialPricing();

        $this->actingAs($this->manager)
            ->getJson('/api/v1/pricing')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pricing->id)
            ->assertJsonPath('data.0.material', null);
    }

    public function test_no_material_quote_skips_material_availability(): void
    {
        $this->makeNoMaterialPricing();
        $this->material->update(['is_active' => false]);
        MaterialBranchStock::query()->delete();

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->noMaterialProduct->id,
                'material_id' => null,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '25000.00')
            ->assertJsonPath('quote.subtotal', '50000.00')
            ->assertJsonMissingPath('quote.cost');
    }

    public function test_no_material_quote_rejects_a_client_material(): void
    {
        $this->actingAs($this->designer)
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
        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->materialProduct->id,
                'material_id' => null,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_id');
    }

    public function test_no_material_invoice_create_skips_material_availability(): void
    {
        $pricing = $this->makeNoMaterialPricing();
        $this->material->update(['is_active' => false]);
        MaterialBranchStock::query()->delete();

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'discount' => 0,
                'items' => [$this->invoiceItem($pricing->id)],
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.orders.0.pricing.material', null)
            ->assertJsonPath('invoice.orders.0.subtotal', '50000.00');

        $this->assertDatabaseHas('orders', [
            'invoice_id' => $response->json('invoice.id'),
            'pricing_id' => $pricing->id,
        ]);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_invoice_show_renders_a_null_material_line(): void
    {
        $pricing = $this->makeNoMaterialPricing();

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [$this->invoiceItem($pricing->id)],
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/invoices/'.$created->json('invoice.id'))
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.pricing.material', null);
    }

    public function test_no_material_invoice_edit_recalculates_an_unchanged_line(): void
    {
        $pricing = $this->makeNoMaterialPricing();

        $created = $this->actingAs($this->designer)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $this->customer->id,
                'items' => [$this->invoiceItem($pricing->id)],
            ])
            ->assertCreated();

        $this->actingAs($this->designer)
            ->putJson('/api/v1/invoices/'.$created->json('invoice.id').'/items', [
                'items' => [$this->invoiceItem($pricing->id, [
                    'id' => $created->json('invoice.orders.0.id'),
                ])],
            ])
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.pricing.material', null)
            ->assertJsonPath('invoice.orders.0.subtotal', '50000.00');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoiceItem(string $pricingId, array $overrides = []): array
    {
        return array_merge([
            'pricing_id' => $pricingId,
            'file_name' => 'desain.pdf',
            'notes' => 'Tanpa bahan',
            'quantity' => 2,
            'is_manual_price' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function pricingPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'product_id' => $this->materialProduct->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
        ], $overrides);
    }

    private function makeNoMaterialPricing(): Pricing
    {
        return Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->noMaterialProduct->id,
            'material_id' => null,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 25000,
            'cost' => 10000,
            'profit' => 15000,
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
