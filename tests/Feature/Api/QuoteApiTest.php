<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private Pricing $pricing;

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

    public function test_quote_options_exclude_inactive_categories_products_and_materials(): void
    {
        $inactiveCategory = ProductCategory::create([
            'name' => 'Retired',
            'is_active' => false,
        ]);

        $inactiveProduct = Product::create([
            'name' => 'Retired Banner',
            'pricing_type' => 'area',
            'product_category_id' => $inactiveCategory->id,
        ]);

        $inactiveMaterial = Material::create([
            'name' => 'Retired Vinyl',
            'product_category_id' => $inactiveCategory->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $inactiveMaterial->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 4,
            'min_stock' => 1,
        ]);

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $categoryIds = collect($response->json('categories'))->pluck('id')->all();
        $productIds = collect($response->json('products'))->pluck('id')->all();
        $materialIds = collect($response->json('materials'))->pluck('id')->all();

        $this->assertNotContains($inactiveCategory->id, $categoryIds);
        $this->assertNotContains($inactiveProduct->id, $productIds);
        $this->assertNotContains($inactiveMaterial->id, $materialIds);

        $this->assertContains($this->product->product_category_id, $categoryIds);
        $this->assertContains($this->product->id, $productIds);
        $this->assertContains($this->material->id, $materialIds);
    }

    public function test_quote_options_keep_products_without_a_category(): void
    {
        $loose = Product::create([
            'name' => 'Loose',
            'pricing_type' => 'unit',
            'product_category_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $productIds = collect($response->json('products'))->pluck('id')->all();

        $this->assertContains($loose->id, $productIds);
    }

    public function test_quote_options_include_inactive_attributes_only_with_the_flag(): void
    {
        $inactive = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Retired Matte',
            'type' => 'finishing',
            'price' => 1000,
            'cost' => 500,
            'is_active' => false,
        ]);

        $inactiveOtherBranch = Attribute::create([
            'branch_id' => $this->branchTwo->id,
            'name' => 'Other Branch Matte',
            'type' => 'finishing',
            'price' => 1000,
            'cost' => 500,
            'is_active' => false,
        ]);

        $withoutFlag = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $ids = collect($withoutFlag->json('attributes'))->pluck('id')->all();

        $this->assertContains($this->attribute->id, $ids);
        $this->assertNotContains($inactive->id, $ids);

        $withFlag = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options?include_inactive_attributes=1')
            ->assertOk();

        $ids = collect($withFlag->json('attributes'))->pluck('id')->all();

        $this->assertContains($this->attribute->id, $ids);
        $this->assertContains($inactive->id, $ids);
        $this->assertNotContains($inactiveOtherBranch->id, $ids);
    }

    public function test_quote_options_flag_does_not_include_trashed_attributes(): void
    {
        $trashed = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Trashed Matte',
            'type' => 'finishing',
            'price' => 1000,
            'cost' => 500,
            'is_active' => false,
        ]);
        $trashed->delete();

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options?include_inactive_attributes=1')
            ->assertOk();

        $ids = collect($response->json('attributes'))->pluck('id')->all();

        $this->assertNotContains($trashed->id, $ids);
    }

    public function test_quote_options_return_all_types_ordered(): void
    {
        $inactiveType = AttributeType::create([
            'name' => 'Stempel',
            'slug' => 'stempel',
            'sort_order' => 4,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->assertJsonStructure([
                'attribute_types' => [['id', 'name', 'slug', 'sort_order', 'is_active']],
            ]);

        $types = collect($response->json('attribute_types'));

        $this->assertSame(
            ['finishing', 'cut', 'display', $inactiveType->slug],
            $types->pluck('slug')->all()
        );
        $this->assertTrue($types->firstWhere('slug', 'finishing')['is_active']);
        $this->assertFalse($types->firstWhere('slug', $inactiveType->slug)['is_active']);
    }

    public function test_quote_options_attributes_include_type_name(): void
    {
        $inactive = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Matte',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $rows = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('attributes')
        );

        $this->assertNotNull($rows->firstWhere('id', $this->attribute->id));
        $this->assertSame('Finishing', $rows->firstWhere('id', $this->attribute->id)['type_name']);
        $this->assertNotContains($inactive->id, $rows->pluck('id')->all());

        $withInactive = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options?include_inactive_attributes=1')
                ->assertOk()
                ->json('attributes')
        );

        $inactiveRow = $withInactive->firstWhere('id', $inactive->id);

        $this->assertNotNull($inactiveRow);
        $this->assertSame('Finishing', $inactiveRow['type_name']);
    }

    public function test_quote_options_eager_load_attribute_type_info(): void
    {
        Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam',
            'type' => 'display',
        ]);
        Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Potong Lurus',
            'type' => 'cut',
        ]);

        $this->product->compatibleAttributes()->sync([$this->attribute->id]);

        DB::enableQueryLog();

        $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $typeQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'attribute_types'))
            ->count();

        // attribute_types list + attributeTypes pivot + attributes typeInfo + compatibleAttributes typeInfo.
        $this->assertSame(4, $typeQueries);
    }

    public function test_quote_options_categories_do_not_report_a_product_count(): void
    {
        $response = $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk();

        $this->assertNotEmpty($response->json('categories'));
        $response->assertJsonMissingPath('categories.0.products_count');
    }

    public function test_product_and_material_resources_report_category_active_state(): void
    {
        $loose = Product::create([
            'name' => 'Loose',
            'pricing_type' => 'unit',
            'product_category_id' => null,
            'is_active' => true,
        ]);

        $activeProducts = $this->actingAs($this->owner)
            ->getJson('/api/v1/products')
            ->assertOk();
        $productRow = collect($activeProducts->json('data'))->firstWhere('id', $this->product->id);

        $this->assertNotNull($productRow);
        $this->assertTrue($productRow['category']['is_active']);

        $this->product->category->update(['is_active' => false]);

        $products = $this->actingAs($this->owner)
            ->getJson('/api/v1/products')
            ->assertOk();
        $productRow = collect($products->json('data'))->firstWhere('id', $this->product->id);
        $looseRow = collect($products->json('data'))->firstWhere('id', $loose->id);

        $this->assertNotNull($productRow);
        $this->assertFalse($productRow['category']['is_active']);
        $this->assertNotNull($looseRow);
        $this->assertNull($looseRow['category']);

        $materials = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials')
            ->assertOk();
        $materialRow = collect($materials->json('data'))->firstWhere('id', $this->material->id);

        $this->assertNotNull($materialRow);
        $this->assertFalse($materialRow['category']['is_active']);
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

        MaterialBranchStock::create([
            'material_id' => $unpriced->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 1,
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

    public function test_quote_rejects_a_soft_deleted_pricing_row(): void
    {
        $this->pricing->delete();

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    public function test_quote_uses_a_custom_type_attribute_price(): void
    {
        $type = AttributeType::create([
            'name' => 'Mata Ayam',
            'slug' => 'mata-ayam',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam 12mm',
            'type' => $type->slug,
            'price' => 5000,
            'cost' => 3000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$attribute->id],
                'quantity' => 2,
                'width' => 2,
                'length' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '55000.00')
            ->assertJsonPath('quote.subtotal', '660000.00')
            ->assertJsonMissingPath('quote.unit_cost')
            ->assertJsonMissingPath('quote.cost')
            ->assertJsonMissingPath('quote.profit');
    }

    public function test_quote_rejects_an_inactive_attribute(): void
    {
        $inactive = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Glossy',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$inactive->id],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');

        $this->assertStringContainsString(
            'atribut',
            strtolower((string) $response->json('errors.quote.0'))
        );
    }

    public function test_quote_rejects_an_attribute_whose_type_is_inactive(): void
    {
        $type = AttributeType::create([
            'name' => 'Stempel',
            'slug' => 'stempel',
            'sort_order' => 4,
            'is_active' => false,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Stempel Karet',
            'type' => $type->slug,
            'price' => 1000,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$attribute->id],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');

        $this->assertStringContainsString(
            'atribut',
            strtolower((string) $response->json('errors.quote.0'))
        );
    }

    public function test_quote_enforces_one_attribute_per_type(): void
    {
        $secondFinishing = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Laminasi Glossy',
            'type' => 'finishing',
            'price' => 1000,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$this->attribute->id, $secondFinishing->id],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');
    }

    public function test_quote_allows_more_than_three_types(): void
    {
        $customType = AttributeType::create([
            'name' => 'Mata Ayam',
            'slug' => 'mata-ayam',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $cut = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Potong Lurus',
            'type' => 'cut',
        ]);
        $display = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'X-Banner',
            'type' => 'display',
        ]);
        $custom = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Mata Ayam 12mm',
            'type' => $customType->slug,
        ]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [
                    $this->attribute->id,
                    $cut->id,
                    $display->id,
                    $custom->id,
                ],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertOk();
    }

    public function test_quote_accepts_an_uppercase_attribute_uuid(): void
    {
        $this->attribute->update(['price' => 5000, 'cost' => 3000]);

        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [strtoupper($this->attribute->id)],
                'quantity' => 2,
                'width' => 2,
                'length' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('quote.unit_price', '55000.00')
            ->assertJsonPath('quote.subtotal', '660000.00');
    }

    public function test_quote_deduplicates_repeated_attribute_ids(): void
    {
        $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$this->attribute->id, $this->attribute->id],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertOk();
    }

    public function test_quote_error_copy_does_not_say_finishing_or_display(): void
    {
        $inactive = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Inactive Glossy',
            'type' => 'finishing',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->designer)
            ->postJson('/api/v1/quotes', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'selected_attributes' => [$inactive->id],
                'quantity' => 1,
                'width' => 2,
                'length' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote');

        $message = strtolower((string) $response->json('errors.quote.0'));

        $this->assertStringContainsString('atribut', $message);
        $this->assertStringNotContainsString('finishing', $message);
        $this->assertStringNotContainsString('display', $message);
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

    public function test_quote_options_hide_globally_inactive_materials(): void
    {
        $this->material->update(['is_active' => false]);

        $inactiveIds = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('materials')
        )->pluck('id')->all();

        $this->assertNotContains($this->material->id, $inactiveIds);

        $this->material->update(['is_active' => true]);

        $activeIds = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('materials')
        )->pluck('id')->all();

        $this->assertContains($this->material->id, $activeIds);
    }

    public function test_quote_options_hide_branch_inactive_materials_and_show_them_in_another_branch(): void
    {
        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 5,
            'min_stock' => 1,
        ]);

        MaterialBranchStock::query()
            ->where('material_id', $this->material->id)
            ->where('branch_id', $this->branchOne->id)
            ->update(['is_active' => false]);

        $branchOneIds = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('materials')
        )->pluck('id')->all();

        $this->assertNotContains($this->material->id, $branchOneIds);

        $branchTwoDesigner = $this->user('designer', $this->branchTwo);

        $branchTwoIds = collect(
            $this->actingAs($branchTwoDesigner)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('materials')
        )->pluck('id')->all();

        $this->assertContains($this->material->id, $branchTwoIds);
    }

    public function test_quote_options_keep_active_materials_with_active_branch_stock(): void
    {
        $ids = collect(
            $this->actingAs($this->designer)
                ->getJson('/api/v1/quotes/options')
                ->assertOk()
                ->json('materials')
        )->pluck('id')->all();

        $this->assertContains($this->material->id, $ids);
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
