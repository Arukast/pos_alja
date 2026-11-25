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

    public function test_manager_creates_material_only_for_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/materials', [
                'name' => 'Manager Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
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

    public function test_machine_crud_is_branch_scoped_for_managers(): void
    {
        $ownerMachine = $this->actingAs($this->owner)
            ->postJson('/api/v1/machines', [
                'name' => 'Branch Two Printer',
                'branch_id' => $this->branchTwo->id,
                'cost' => 1000,
            ])
            ->assertCreated()
            ->json('machine');

        $managerMachine = $this->actingAs($this->manager)
            ->postJson('/api/v1/machines', [
                'name' => 'Branch One Printer',
                'branch_id' => $this->branchTwo->id,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('machine.branch_id', $this->branchOne->id)
            ->json('machine');

        $this->actingAs($this->manager)
            ->putJson("/api/v1/machines/{$ownerMachine['id']}", [
                'name' => 'Nope',
                'branch_id' => $this->branchTwo->id,
                'cost' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->deleteJson("/api/v1/machines/{$managerMachine['id']}")
            ->assertNoContent();

        $this->actingAs($this->manager)
            ->postJson("/api/v1/machines/{$managerMachine['id']}/restore")
            ->assertOk();
    }

    public function test_category_attribute_type_and_attribute_crud(): void
    {
        $category = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Indoor',
                'calculation_archetype' => 'per_piece',
                'allowed_attribute_types' => ['finishing'],
            ])
            ->assertCreated()
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Updated',
                'calculation_archetype' => 'per_piece',
                'allowed_attribute_types' => ['finishing'],
            ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Indoor Updated');

        $attributeType = $this->actingAs($this->manager)
            ->postJson('/api/v1/attribute-types', [
                'name' => 'Lamination',
                'calculation_archetype' => 'per_piece',
            ])
            ->assertCreated()
            ->json('attribute_type');

        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy',
                'type' => 'finishing',
                'branch_id' => $this->branchTwo->id,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchOne->id);

        $this->actingAs($this->manager)
            ->deleteJson("/api/v1/attribute-types/{$attributeType['id']}")
            ->assertNoContent();

        foreach ([$this->manager, $this->cashier] as $actor) {
            $this->actingAs($actor)
                ->postJson('/api/v1/categories', [
                    'name' => 'Nope',
                    'calculation_archetype' => 'per_piece',
                ])
                ->assertForbidden();
        }
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
        foreach (['/materials', '/machines', '/categories', '/attribute-types', '/attributes', '/pricing'] as $path) {
            $this->postJson('/api/v1'.$path, [])->assertUnauthorized();
        }
    }

    public function test_catalog_write_validation_errors_are_422(): void
    {
        $actor = $this->manager;

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Invalid',
                'calculation_archetype' => 'not-real',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('calculation_archetype');

        $this->actingAs($actor)
            ->postJson('/api/v1/attribute-types', [
                'name' => 'Invalid',
                'calculation_archetype' => 'not-real',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('calculation_archetype');

        $this->actingAs($actor)
            ->postJson('/api/v1/attributes', [
                'name' => 'Invalid',
                'type' => 'not-real',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

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
            ])
            ->assertCreated()
            ->assertJsonPath('product.is_active', true)
            ->json('product');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'model_type' => Product::class,
            'model_id' => $created['id'],
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$created['id']}", [
                'name' => 'Banner Baru Updated',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('product.is_active', false)
            ->assertJsonPath('product.pricing_type', 'unit');

        $updateLog = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Product::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertTrue((bool) $updateLog->payload['before']['is_active']);
        $this->assertFalse((bool) $updateLog->payload['after']['is_active']);

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
    }

    public function test_owner_can_manage_category_quantity_discount_tiers_and_non_owners_are_denied(): void
    {
        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Print Lembaran',
                'calculation_archetype' => 'per_piece',
                'allowed_attribute_types' => ['finishing'],
                'discount_tiers' => [
                    ['min_qty' => 50, 'discount_percent' => 10],
                    ['min_qty' => 10, 'discount_percent' => 5.5],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('category.discount_tiers.0.min_qty', 10)
            ->assertJsonPath('category.discount_tiers.0.discount_percent', '5.50')
            ->assertJsonPath('category.discount_tiers.1.min_qty', 50)
            ->assertJsonPath('category.discount_tiers.1.discount_percent', '10.00')
            ->json('category');

        $this->assertDatabaseHas('product_category_discounts', [
            'product_category_id' => $created['id'],
            'min_qty' => 10,
            'discount_percent' => 5.5,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$created['id']}", [
                'name' => 'Print Lembaran Update',
                'calculation_archetype' => 'per_piece',
                'allowed_attribute_types' => ['finishing'],
                'discount_tiers' => [
                    ['min_qty' => 20, 'discount_percent' => 7.25],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'category.discount_tiers')
            ->assertJsonPath('category.discount_tiers.0.min_qty', 20)
            ->assertJsonPath('category.discount_tiers.0.discount_percent', '7.25');

        $this->assertDatabaseMissing('product_category_discounts', [
            'product_category_id' => $created['id'],
            'min_qty' => 10,
        ]);
        $this->assertDatabaseMissing('product_category_discounts', [
            'product_category_id' => $created['id'],
            'min_qty' => 50,
        ]);
        $this->assertDatabaseHas('product_category_discounts', [
            'product_category_id' => $created['id'],
            'min_qty' => 20,
            'discount_percent' => 7.25,
        ]);

        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $blockedPayload = [
            'name' => 'Blocked Category',
            'calculation_archetype' => 'per_piece',
        ];

        foreach ([$this->manager, $this->cashier, $designer, $production] as $actor) {
            $this->actingAs($actor)->postJson('/api/v1/categories', $blockedPayload)->assertForbidden();
            $this->actingAs($actor)->putJson("/api/v1/categories/{$created['id']}", $blockedPayload)->assertForbidden();
            $this->actingAs($actor)->deleteJson("/api/v1/categories/{$created['id']}")->assertForbidden();
        }

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/categories/{$created['id']}")
            ->assertNoContent();

        $this->assertDatabaseMissing('product_category_discounts', [
            'product_category_id' => $created['id'],
        ]);
    }

    public function test_category_discount_tier_validation_errors_are_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Duplicate Tier',
                'calculation_archetype' => 'per_piece',
                'discount_tiers' => [
                    ['min_qty' => 10, 'discount_percent' => 5],
                    ['min_qty' => 10, 'discount_percent' => 10],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_tiers.1.min_qty');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Zero Qty Tier',
                'calculation_archetype' => 'per_piece',
                'discount_tiers' => [
                    ['min_qty' => 0, 'discount_percent' => 5],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_tiers.0.min_qty');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Negative Discount Tier',
                'calculation_archetype' => 'per_piece',
                'discount_tiers' => [
                    ['min_qty' => 5, 'discount_percent' => -1],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_tiers.0.discount_percent');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Over Cap Discount Tier',
                'calculation_archetype' => 'per_piece',
                'discount_tiers' => [
                    ['min_qty' => 5, 'discount_percent' => 101],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_tiers.0.discount_percent');
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
