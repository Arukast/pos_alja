<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private ProductCategory $category;

    private Product $product;

    private Material $material;

    private Material $otherMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->product = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $this->category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);

        $this->otherMaterial = Material::create([
            'name' => 'Paper Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 500,
            'price' => 1000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
            'min_stock' => 1,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
            'min_stock' => 1,
        ]);
    }

    public function test_owner_creates_material_and_initializes_all_branch_stock(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'New Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'roll',
                'cost' => 1000,
                'price' => 2000,
                'initial_stock' => 10,
                'min_stock' => 2,
            ])
            ->assertCreated();

        $this->assertDatabaseCount('material_branch_stock', 4);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 10,
        ]);
    }

    public function test_material_create_requires_a_unit_from_the_fixed_set(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Unitless',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Unknown Unit',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'sheet',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Measured Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'm2',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('material.unit', 'm2');

        $this->assertDatabaseHas('materials', [
            'name' => 'Measured Vinyl',
            'unit' => 'm2',
        ]);
    }

    public function test_material_update_accepts_an_optional_unit_and_rejects_unknown_units(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('material.unit', 'pcs');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'kg',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('material.unit', 'kg');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'sheet',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->assertDatabaseHas('materials', [
            'id' => $this->material->id,
            'unit' => 'kg',
        ]);
    }

    public function test_manager_creates_material_only_for_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/materials', [
                'name' => 'Manager Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'roll',
                'cost' => 1000,
                'price' => 2000,
                'initial_stock' => 4,
                'min_stock' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchOne->id,
        ]);
        $this->assertDatabaseMissing('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchTwo->id,
        ]);

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/materials', [
                'name' => 'Nope',
                'product_category_id' => $this->category->id,
                'unit' => 'pcs',
            ])
            ->assertForbidden();
    }

    public function test_similar_material_lookup_returns_matches(): void
    {
        $this->actingAs($this->manager)
            ->getJson('/api/v1/materials/similar?name=Vinyl')
            ->assertOk()
            ->assertJsonFragment(['id' => $this->material->id]);
    }

    public function test_restock_is_forced_to_the_manager_branch(): void
    {
        $this->actingAs($this->manager)
            ->postJson("/api/v1/materials/{$this->material->id}/stock", [
                'quantity' => 3,
                'notes' => 'Restock',
                'branch_id' => $this->branchTwo->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->material->id,
            'added_stock' => 3,
        ]);
    }

    public function test_global_material_delete_is_owner_only(): void
    {
        $this->actingAs($this->manager)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('materials', ['id' => $this->material->id]);
    }

    public function test_manager_cannot_update_global_material_master(): void
    {
        $this->actingAs($this->manager)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Manager Rename',
                'product_category_id' => $this->category->id,
                'cost' => 1,
                'price' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Owner Rename',
                'product_category_id' => $this->category->id,
                'cost' => 1200,
                'price' => 2400,
            ])
            ->assertOk()
            ->assertJsonPath('material.cost', '1200.00')
            ->assertJsonPath('material.price', '2400.00');
    }

    public function test_material_restore_keeps_branch_stock(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('materials', ['id' => $this->material->id]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$this->material->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
        ]);
    }

    public function test_manager_material_list_hides_other_branch_stock(): void
    {
        $managerResponse = $this->actingAs($this->manager)
            ->getJson('/api/v1/materials')
            ->assertOk();

        $managerRow = collect($managerResponse->json('data'))->firstWhere('id', $this->material->id);

        $this->assertCount(1, $managerRow['stocks']);
        $this->assertSame($this->branchOne->id, $managerRow['stocks'][0]['branch_id']);

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk();

        $ownerRow = collect($ownerResponse->json('data'))->firstWhere('id', $this->material->id);

        $this->assertCount(2, $ownerRow['stocks']);
    }

    public function test_category_and_attribute_crud(): void
    {
        $category = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Indoor',
            ])
            ->assertCreated()
            ->assertJsonMissingPath('category.calculation_archetype')
            ->assertJsonMissingPath('category.allowed_attribute_types')
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Updated',
            ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Indoor Updated')
            ->assertJsonMissingPath('category.calculation_archetype')
            ->assertJsonMissingPath('category.allowed_attribute_types');

        $attribute = $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy',
                'type' => 'finishing',
                'branch_id' => $this->branchTwo->id,
                'price' => 5000,
                'cost' => 2000,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchTwo->id)
            ->assertJsonPath('attribute.price', '5000.00')
            ->assertJsonPath('attribute.cost', '2000.00')
            ->assertJsonPath('attribute.is_active', true)
            ->json('attribute');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/attributes/{$attribute['id']}", [
                'name' => 'Glossy Matte',
                'type' => 'display',
                'branch_id' => $this->branchOne->id,
                'price' => 6000,
                'cost' => 2500,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.type', 'display')
            ->assertJsonPath('attribute.price', '6000.00')
            ->assertJsonPath('attribute.cost', '2500.00')
            ->assertJsonPath('attribute.is_active', false);

        foreach ([$this->manager, $this->cashier] as $actor) {
            $this->actingAs($actor)
                ->postJson('/api/v1/categories', [
                    'name' => 'Nope',
                    'calculation_archetype' => 'per_piece',
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->postJson('/api/v1/attributes', [
                    'name' => 'Nope Attr',
                    'type' => 'finishing',
                    'price' => 1000,
                    'cost' => 500,
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->putJson("/api/v1/attributes/{$attribute['id']}", [
                    'name' => 'Nope Attr Update',
                    'type' => 'finishing',
                    'price' => 1000,
                    'cost' => 500,
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->deleteJson("/api/v1/attributes/{$attribute['id']}")
                ->assertForbidden();
        }

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/attributes/{$attribute['id']}")
            ->assertNoContent();
    }

    public function test_pricing_crud_and_clone_tiers(): void
    {
        $pricing = $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', [
                'branch_id' => $this->branchOne->id,
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'customer_type' => 'Reguler',
                'min_qty' => 1,
                'price' => 50000,
                'cost' => 20000,
            ])
            ->assertCreated()
            ->json('pricing');

        $this->actingAs($this->manager)
            ->putJson("/api/v1/pricing/{$pricing['id']}", [
                'min_qty' => 1,
                'price' => 55000,
                'cost' => 20000,
            ])
            ->assertOk()
            ->assertJsonPath('pricing.price', '55000.00');

        $this->actingAs($this->manager)
            ->postJson('/api/v1/pricing/clone-tiers', [
                'from_material_id' => $this->material->id,
                'to_material_id' => $this->otherMaterial->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('pricing', [
            'branch_id' => $this->branchOne->id,
            'material_id' => $this->otherMaterial->id,
            'customer_type' => 'Reguler',
        ]);
    }

    public function test_catalog_write_endpoints_require_authentication(): void
    {
        foreach (['/materials', '/categories', '/attributes', '/pricing'] as $path) {
            $this->postJson('/api/v1'.$path, [])->assertUnauthorized();
        }

        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->putJson("/api/v1/categories/{$this->category->id}", ['name' => 'Guest'])
            ->assertUnauthorized();
    }

    public function test_catalog_write_validation_errors_are_422(): void
    {
        $actor = $this->manager;

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Ignored Legacy Fields',
                'calculation_archetype' => 'not-real',
                'allowed_attribute_types' => ['finishing'],
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Ignored Legacy Fields')
            ->assertJsonMissingPath('category.calculation_archetype')
            ->assertJsonMissingPath('category.allowed_attribute_types');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Invalid',
                'type' => 'not-real',
                'price' => 1000,
                'cost' => 500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Negative Price',
                'type' => 'finishing',
                'price' => -1,
                'cost' => 500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Missing Cost',
                'type' => 'display',
                'price' => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cost');

        $this->actingAs($actor)
            ->postJson('/api/v1/pricing', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'customer_type' => 'Reguler',
                'min_qty' => 1,
                'cost' => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    public function test_product_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->putJson("/api/v1/products/{$this->product->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/products/{$this->product->id}")->assertUnauthorized();
        $this->postJson("/api/v1/products/{$this->product->id}/restore")->assertUnauthorized();
    }

    public function test_owner_can_create_update_delete_and_restore_products(): void
    {
        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Banner Baru',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'area',
                'material_ids' => [$this->material->id],
            ])
            ->assertCreated()
            ->assertJsonPath('product.is_active', true)
            ->assertJsonPath('product.material_ids.0', $this->material->id)
            ->assertJsonPath('product.materials.0.id', $this->material->id)
            ->json('product');

        $this->assertDatabaseHas('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->material->id,
        ]);

        $createLog = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Product::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($createLog);
        $this->assertSame([$this->material->id], $createLog->payload['material_ids']);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$created['id']}", [
                'name' => 'Banner Baru Updated',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'is_active' => false,
                'material_ids' => [$this->otherMaterial->id],
            ])
            ->assertOk()
            ->assertJsonPath('product.is_active', false)
            ->assertJsonPath('product.pricing_type', 'unit')
            ->assertJsonPath('product.material_ids.0', $this->otherMaterial->id);

        $this->assertDatabaseMissing('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->material->id,
        ]);
        $this->assertDatabaseHas('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->otherMaterial->id,
        ]);

        $updateLog = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Product::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertTrue((bool) $updateLog->payload['before']['is_active']);
        $this->assertFalse((bool) $updateLog->payload['after']['is_active']);
        $this->assertSame([$this->material->id], $updateLog->payload['before']['material_ids']);
        $this->assertSame([$this->otherMaterial->id], $updateLog->payload['after']['material_ids']);

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/products/{$created['id']}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $created['id']]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'model_type' => Product::class,
            'model_id' => $created['id'],
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$created['id']}/restore")
            ->assertOk()
            ->assertJsonPath('product.deleted_at', null);

        $this->assertNotSoftDeleted('products', ['id' => $created['id']]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'RESTORE',
            'model_type' => Product::class,
            'model_id' => $created['id'],
        ]);
    }

    public function test_unauthorized_roles_and_branchless_manager_cannot_manage_products(): void
    {
        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $branchlessManager = $this->user('manager', null);

        $payload = [
            'name' => 'Blocked Product',
            'product_category_id' => $this->category->id,
            'pricing_type' => 'unit',
        ];

        foreach ([$this->manager, $this->cashier, $designer, $production, $branchlessManager] as $actor) {
            $this->actingAs($actor)->postJson('/api/v1/products', $payload)->assertForbidden();
            $this->actingAs($actor)->putJson("/api/v1/products/{$this->product->id}", $payload)->assertForbidden();
            $this->actingAs($actor)->deleteJson("/api/v1/products/{$this->product->id}")->assertForbidden();
            $this->actingAs($actor)->postJson("/api/v1/products/{$this->product->id}/restore")->assertForbidden();
        }
    }

    public function test_product_write_validation_errors_are_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Invalid Pricing Type',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'weight',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pricing_type');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Missing Category',
                'product_category_id' => (string) Str::uuid(),
                'pricing_type' => 'unit',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_category_id');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Invalid Active Flag',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'is_active' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Invalid Material Mapping',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'material_ids' => [(string) Str::uuid()],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_ids.0');

        $deletedMaterial = Material::create([
            'name' => 'Deleted Material',
            'product_category_id' => $this->category->id,
            'unit' => 'pcs',
            'cost' => 1000,
            'price' => 2000,
        ]);
        $deletedMaterial->delete();

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Deleted Material Mapping',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'material_ids' => [$deletedMaterial->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_ids.0');
    }

    public function test_owner_creates_and_updates_a_category_with_description_and_active_state(): void
    {
        $category = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Indoor',
                'description' => 'Bahan indoor',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('category.description', 'Bahan indoor')
            ->assertJsonPath('category.is_active', true)
            ->assertJsonMissingPath('category.products_count')
            ->assertJsonMissingPath('category.discount_tiers')
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Updated',
                'description' => null,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', null)
            ->assertJsonPath('category.is_active', false);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category['id'],
            'description' => null,
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', null)
            ->assertJsonPath('category.is_active', true);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'description' => 'Bahan indoor lagi',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', 'Bahan indoor lagi');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', 'Bahan indoor lagi');
    }

    public function test_category_delete_endpoint_is_removed(): void
    {
        $this->deleteJson("/api/v1/categories/{$this->category->id}")
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/categories/{$this->category->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('product_categories', ['id' => $this->category->id]);
    }

    public function test_category_write_validation_errors_are_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'description' => 'Missing name',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Long Description',
                'description' => str_repeat('a', 256),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Array Description',
                'description' => ['nope'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Invalid Active Flag',
                'is_active' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active');
    }

    public function test_legacy_discount_tiers_are_ignored(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Legacy',
                'discount_tiers' => [
                    ['min_qty' => 10, 'discount_percent' => 5],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Legacy');

        $this->assertFalse(Schema::hasTable('product_category_discounts'));
    }

    public function test_restore_product_is_inactive_when_its_category_is_inactive(): void
    {
        $inactiveCategory = ProductCategory::create([
            'name' => 'Retired',
            'is_active' => false,
        ]);

        $inactiveProduct = Product::create([
            'name' => 'Retired Banner',
            'pricing_type' => 'unit',
            'product_category_id' => $inactiveCategory->id,
            'is_active' => true,
        ]);
        $inactiveProduct->delete();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$inactiveProduct->id}/restore")
            ->assertOk()
            ->assertJsonPath('product.is_active', false);

        $this->assertDatabaseHas('products', [
            'id' => $inactiveProduct->id,
            'is_active' => false,
        ]);

        $this->product->delete();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$this->product->id}/restore")
            ->assertOk()
            ->assertJsonPath('product.is_active', true);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'is_active' => true,
        ]);
    }

    public function test_category_writes_are_owner_only(): void
    {
        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $payload = ['name' => 'Blocked Category'];

        foreach ([$this->manager, $this->cashier, $designer, $production] as $actor) {
            $this->actingAs($actor)->postJson('/api/v1/categories', $payload)->assertForbidden();
            $this->actingAs($actor)
                ->putJson("/api/v1/categories/{$this->category->id}", $payload)
                ->assertForbidden();
        }

        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', ['name' => 'Owner Category'])
            ->assertCreated()
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$created['id']}", [
                'name' => 'Owner Category Updated',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Owner Category Updated')
            ->assertJsonPath('category.is_active', false);

        $this->assertFalse(Schema::hasTable('product_category_discounts'));
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
