<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuoteApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $designer;

    private User $cashier;

    private User $owner;

    private Product $product;

    private Material $material;

    private Attribute $attribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->designer = $this->user('designer', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->owner = $this->user('owner', null);

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

        $this->product->materials()->sync([$this->material->id]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 1,
        ]);

        Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
        ]);
    }

    public function test_designer_can_fetch_quote_options_without_cost_or_price(): void
    {
        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->assertJsonStructure([
                'categories',
                'products',
                'materials',
                'attributes',
            ])
            ->assertJsonMissingPath('machines')
            ->assertJsonMissingPath('materials.0.cost')
            ->assertJsonMissingPath('materials.0.price')
            ->assertJsonMissingPath('products.0.materials.0.cost')
            ->assertJsonMissingPath('products.0.materials.0.price')
            ->assertJsonPath('products.0.material_ids.0', $this->material->id);

        $this->assertSame($this->material->id, $response->json('materials.0.id'));
    }

    public function test_quote_options_excludes_inactive_products(): void
    {
        $inactive = Product::create([
            'name' => 'Inactive Banner',
            'pricing_type' => 'area',
            'product_category_id' => $this->product->product_category_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $ids = collect($response->json('products'))->pluck('id')->all();

        $this->assertContains($this->product->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_quote_options_excludes_inactive_attributes(): void
    {
        $inactive = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Matte',
            'type' => 'finishing',
            'price' => 1000,
            'cost' => 500,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $ids = collect($response->json('attributes'))->pluck('id')->all();

        $this->assertContains($this->attribute->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_cashier_cannot_fetch_quote_options(): void
    {
        $this->actingAs($this->cashier)
            ->getJson('/api/v1/quotes/options')
            ->assertForbidden();
    }

    public function test_designer_can_calculate_a_quote_without_server_cost_leakage(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                // Legacy clients may still send machine_id; it must be ignored.
                'machine_id' => (string) Str::uuid(),
                'quantity' => 2,
                'width' => 2,
                'length' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '50000.00')
            ->assertJsonPath('quote.area', 6)
            ->assertJsonPath('quote.subtotal', '600000.00')
            ->assertJsonMissingPath('quote.unit_cost')
            ->assertJsonMissingPath('quote.cost')
            ->assertJsonMissingPath('quote.profit');
    }

    public function test_quote_returns_validation_error_when_pricing_is_missing(): void
    {
        $unpriced = Material::create([
            'name' => 'Unpriced',
            'product_category_id' => $this->product->product_category_id,
            'type' => 'roll',
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $unpriced->id,
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    public function test_non_designer_cannot_calculate_a_quote(): void
    {
        foreach ([$this->cashier, $this->owner] as $user) {
            $this->actingAs($user)
                ->postJson('/api/v1/quotes', [
                    'product_id' => $this->product->id,
                    'material_id' => $this->material->id,
                    'quantity' => 1,
                    'width' => 2,
                    'length' => 3,
                ])
                ->assertForbidden();
        }
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
