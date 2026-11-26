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
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private ProductCategory $category;

    private Material $material;

    private Attribute $attributeOne;

    private Attribute $attributeTwo;

    private Pricing $pricingOne;

    private Pricing $pricingTwo;

    private StockLedger $ledgerOne;

    private StockLedger $ledgerTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->designer = $this->user('designer', $this->branchOne);
        $this->production = $this->user('production', $this->branchOne);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $vinylBanner = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $this->category->id,
        ]);

        Product::create([
            'name' => 'Flyer',
            'pricing_type' => 'unit',
            'product_category_id' => $this->category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'dimensions' => '1.2m',
            'unit' => 'm2',
            'cost' => 10000,
            'price' => 15000,
        ]);

        $vinylBanner->materials()->sync([$this->material->id]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 2,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 3,
            'min_stock' => 5,
        ]);

        AttributeType::firstOrCreate(
            ['name' => 'finishing'],
            ['calculation_archetype' => 'per_piece']
        );

        $this->attributeOne = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
        ]);

        $this->attributeTwo = Attribute::create([
            'branch_id' => $this->branchTwo->id,
            'name' => 'Matte',
            'type' => 'finishing',
        ]);

        $this->pricingOne = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $this->category->products()->first()->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 20000,
            'profit' => 30000,
        ]);

        $this->pricingTwo = Pricing::create([
            'branch_id' => $this->branchTwo->id,
            'product_id' => $this->category->products()->first()->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 60000,
            'cost' => 25000,
            'profit' => 35000,
        ]);

        $this->ledgerOne = StockLedger::create([
            'branch_id' => $this->branchOne->id,
            'material_id' => $this->material->id,
            'type' => 'purchase',
            'reason' => 'Pembelian bahan baku',
            'user_id' => $this->manager->id,
            'reference_type' => 'purchase',
            'reference_id' => (string) Str::uuid(),
            'logged_at' => now(),
            'initial_stock' => 0,
            'added_stock' => 10,
            'used_stock' => 0,
            'wasted_stock' => 0,
            'remaining_stock' => 10,
            'notes' => 'Pembelian bahan baku',
        ]);

        $this->ledgerTwo = StockLedger::create([
            'branch_id' => $this->branchTwo->id,
            'material_id' => $this->material->id,
            'logged_at' => now(),
            'initial_stock' => 0,
            'added_stock' => 3,
            'used_stock' => 0,
            'wasted_stock' => 0,
            'remaining_stock' => 3,
            'notes' => 'Initial stock',
        ]);
    }

    public function test_owner_can_read_categories_attribute_types_attributes_products_and_pricing(): void
    {
        foreach ([
            '/api/v1/categories',
            '/api/v1/attribute-types',
            '/api/v1/attributes',
            '/api/v1/products',
            '/api/v1/materials',
            '/api/v1/pricing',
            '/api/v1/stock-ledgers',
        ] as $path) {
            $this->actingAs($this->owner)->getJson($path)->assertOk();
        }
    }

    public function test_machine_endpoints_are_removed(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/machines')
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/machines', [])
            ->assertNotFound();
    }

    public function test_manager_reads_own_branch_catalog_scope(): void
    {
        $attributes = $this->actingAs($this->manager)
            ->getJson('/api/v1/attributes')
            ->assertOk()
            ->json('data');

        $this->assertContains($this->attributeOne->id, collect($attributes)->pluck('id')->all());
        $this->assertNotContains($this->attributeTwo->id, collect($attributes)->pluck('id')->all());

        $this->actingAs($this->manager)
            ->getJson('/api/v1/pricing?branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->pricingOne->id);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/stock-ledgers?branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->ledgerOne->id);
    }

    public function test_cashier_and_designer_can_read_products_but_not_management_catalogs(): void
    {
        foreach ([$this->cashier, $this->designer] as $user) {
            $this->actingAs($user)->getJson('/api/v1/products')->assertOk();
            $this->actingAs($user)->getJson('/api/v1/categories')->assertForbidden();
            $this->actingAs($user)->getJson('/api/v1/attribute-types')->assertForbidden();
            $this->actingAs($user)->getJson('/api/v1/attributes')->assertForbidden();
            $this->actingAs($user)->getJson('/api/v1/materials')->assertForbidden();
            $this->actingAs($user)->getJson('/api/v1/pricing')->assertForbidden();
        }
    }

    public function test_production_can_read_materials_without_money_fields(): void
    {
        $this->actingAs($this->production)->getJson('/api/v1/products')->assertForbidden();

        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/materials')
            ->assertOk()
            ->assertJsonMissingPath('data.0.cost')
            ->assertJsonMissingPath('data.0.price');

        $this->assertEquals(10, (float) $response->json('data.0.current_stock'));
        $this->assertSame($this->material->id, $response->json('data.0.id'));
        $this->assertSame('m2', $response->json('data.0.unit'));
    }

    public function test_manager_material_list_is_scoped_to_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/materials?branch_id='.$this->branchTwo->id)
            ->assertOk();

        $this->assertEquals(10, (float) $response->json('data.0.current_stock'));
        $this->assertCount(1, $response->json('data.0.stocks'));
        $this->assertSame($this->branchOne->id, $response->json('data.0.stocks.0.branch_id'));
    }

    public function test_owner_can_read_materials_across_branches(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk();

        $this->assertCount(2, $response->json('data.0.stocks'));
        $this->assertNull($response->json('data.0.current_stock'));
    }

    public function test_product_search_filters_results(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?search=Banner')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vinyl Banner')
            ->assertJsonPath('data.0.material_ids.0', $this->material->id)
            ->assertJsonPath('data.0.materials.0.name', 'Vinyl Roll');
    }

    public function test_owner_stock_ledgers_can_show_all_branches(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/stock-ledgers?branch_id=all')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($this->ledgerOne->id, $ids);
        $this->assertContains($this->ledgerTwo->id, $ids);
    }

    public function test_products_are_paginated_at_ten_per_page(): void
    {
        for ($index = 1; $index <= 23; $index++) {
            Product::create([
                'name' => sprintf('Produk %02d', $index),
                'pricing_type' => 'unit',
                'product_category_id' => $this->category->id,
            ]);
        }

        $pageOne = $this->actingAs($this->owner)
            ->getJson('/api/v1/products?page=1')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 25);

        $pageTwo = $this->actingAs($this->owner)
            ->getJson('/api/v1/products?page=2')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2);

        $pageThree = $this->actingAs($this->owner)
            ->getJson('/api/v1/products?page=3')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 3);

        $pageOneIds = collect($pageOne->json('data'))->pluck('id')->all();
        $pageTwoIds = collect($pageTwo->json('data'))->pluck('id')->all();
        $pageThreeIds = collect($pageThree->json('data'))->pluck('id')->all();

        $this->assertEmpty(array_intersect($pageOneIds, $pageTwoIds));
        $this->assertEmpty(array_intersect($pageOneIds, $pageThreeIds));
        $this->assertEmpty(array_intersect($pageTwoIds, $pageThreeIds));
        $this->assertCount(25, array_unique([...$pageOneIds, ...$pageTwoIds, ...$pageThreeIds]));
    }

    public function test_product_search_is_case_insensitive_and_filters_by_category_and_active_status(): void
    {
        $secondCategory = ProductCategory::create([
            'name' => 'Print Lembaran',
            'calculation_archetype' => 'per_piece',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $inactiveProduct = Product::create([
            'name' => 'Goodie Bag',
            'pricing_type' => 'unit',
            'product_category_id' => $secondCategory->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?search=banner')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vinyl Banner');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?search=BANNER')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vinyl Banner');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?search=lembaran')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactiveProduct->id);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?is_active=1')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $inactiveProduct->id]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactiveProduct->id);

        $byCategory = $this->actingAs($this->owner)
            ->getJson('/api/v1/products?category_id='.$secondCategory->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactiveProduct->id);

        $this->assertStringContainsString(
            "category_id={$secondCategory->id}",
            (string) $byCategory->json('links.first')
        );
    }

    public function test_branchless_non_owner_cannot_read_products(): void
    {
        foreach (['manager', 'cashier', 'designer'] as $role) {
            $branchlessUser = $this->user($role, null);

            $this->actingAs($branchlessUser)
                ->getJson('/api/v1/products')
                ->assertForbidden();
        }
    }

    public function test_include_deleted_products_is_restricted_to_owner(): void
    {
        $deleted = Product::create([
            'name' => 'Deleted Banner',
            'pricing_type' => 'unit',
            'product_category_id' => $this->category->id,
        ]);
        $deleted->delete();

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonMissing(['id' => $deleted->id]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products?include_deleted=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $deleted->id]);

        foreach ([$this->manager, $this->cashier, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson('/api/v1/products?include_deleted=1')
                ->assertOk()
                ->assertJsonMissing(['id' => $deleted->id]);
        }
    }

    public function test_categories_expose_description_active_state_and_product_count(): void
    {
        $vinylBanner = Product::query()->where('name', 'Vinyl Banner')->firstOrFail();

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/categories')
            ->assertOk();

        $category = collect($response->json('data'))->firstWhere('id', $this->category->id);

        $this->assertNotNull($category);
        $this->assertNull($category['description']);
        $this->assertTrue($category['is_active']);
        $this->assertSame(2, $category['products_count']);
        $this->assertArrayNotHasKey('discount_tiers', $category);

        $vinylBanner->delete();

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/categories')
            ->assertOk();

        $category = collect($response->json('data'))->firstWhere('id', $this->category->id);

        $this->assertNotNull($category);
        $this->assertSame(1, $category['products_count']);

        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->getJson('/api/v1/categories')
            ->assertForbidden();
    }

    public function test_category_deactivation_is_allowed_and_does_not_touch_products(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$this->category->id}", [
                'name' => 'Large Format',
                'description' => 'Cetak besar',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('category.is_active', false)
            ->assertJsonPath('category.description', 'Cetak besar');

        $this->assertDatabaseHas('product_categories', [
            'id' => $this->category->id,
            'description' => 'Cetak besar',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('products', ['name' => 'Vinyl Banner', 'is_active' => true]);
        $this->assertDatabaseHas('products', ['name' => 'Flyer', 'is_active' => true]);
    }

    public function test_manager_reads_attribute_pricing_and_branchless_manager_is_denied(): void
    {
        $this->attributeOne->update([
            'price' => 3500,
            'cost' => 1500,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/attributes')
            ->assertOk();

        $attribute = collect($response->json('data'))->firstWhere('id', $this->attributeOne->id);

        $this->assertNotNull($attribute);
        $this->assertSame('3500.00', $attribute['price']);
        $this->assertSame('1500.00', $attribute['cost']);
        $this->assertTrue($attribute['is_active']);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/attribute-types')
            ->assertOk();

        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->getJson('/api/v1/attributes')
            ->assertForbidden();

        $this->actingAs($branchlessManager)
            ->getJson('/api/v1/attribute-types')
            ->assertForbidden();
    }

    public function test_stock_ledgers_carry_traceability_fields(): void
    {
        $this->actingAs($this->manager)
            ->getJson('/api/v1/stock-ledgers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->ledgerOne->id)
            ->assertJsonPath('data.0.material.unit', 'm2')
            ->assertJsonPath('data.0.type', 'purchase')
            ->assertJsonPath('data.0.reason', 'Pembelian bahan baku')
            ->assertJsonPath('data.0.user.id', $this->manager->id)
            ->assertJsonPath('data.0.user.name', $this->manager->name)
            ->assertJsonPath('data.0.reference_type', 'purchase')
            ->assertJsonPath('data.0.reference_id', $this->ledgerOne->reference_id);
    }

    public function test_stock_ledger_returns_a_null_material_when_it_is_soft_deleted(): void
    {
        $this->material->delete();

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/stock-ledgers')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertNull($response->json('data.0.material'));
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
