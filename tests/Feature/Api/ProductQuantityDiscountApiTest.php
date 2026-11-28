<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProductQuantityDiscountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $branchlessManager;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $branch);
        $this->cashier = $this->user('cashier', $branch);
        $this->branchlessManager = $this->user('manager', null);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->product = Product::create([
            'name' => 'Vinyl Banner',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);
    }

    public function test_guest_receives_401_on_all_tier_endpoints(): void
    {
        $this->getJson('/api/v1/product-quantity-discounts')
            ->assertUnauthorized();

        $this->postJson('/api/v1/product-quantity-discounts', [
            'product_id' => $this->product->id,
            'min_qty' => 10,
            'discount_percent' => 15,
        ])->assertUnauthorized();
    }

    public function test_cashier_receives_403_on_tier_index_and_store(): void
    {
        $this->actingAs($this->cashier)
            ->getJson('/api/v1/product-quantity-discounts')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 10,
                'discount_percent' => 15,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_read_tiers_but_cannot_write_them(): void
    {
        DB::table('product_quantity_discounts')->insert([
            'id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'min_qty' => 5,
            'discount_percent' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/product-quantity-discounts')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.min_qty', 5)
            ->assertJsonPath('data.0.discount_percent', '10.00')
            ->assertJsonPath('data.0.product.name', $this->product->name);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 20,
                'discount_percent' => 25,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('product_quantity_discounts', 1);
    }

    public function test_owner_can_create_update_and_delete_a_tier(): void
    {
        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 10,
                'discount_percent' => 15,
            ])
            ->assertCreated()
            ->assertJsonPath('tier.min_qty', 10)
            ->assertJsonPath('tier.discount_percent', '15.00')
            ->json('tier');

        $id = $created['id'];

        $this->assertDatabaseHas('product_quantity_discounts', [
            'id' => $id,
            'product_id' => $this->product->id,
            'min_qty' => 10,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/product-quantity-discounts/{$id}", [
                'min_qty' => 20,
                'discount_percent' => 25,
            ])
            ->assertOk()
            ->assertJsonPath('tier.min_qty', 20)
            ->assertJsonPath('tier.discount_percent', '25.00');

        $this->assertDatabaseHas('product_quantity_discounts', [
            'id' => $id,
            'min_qty' => 20,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/product-quantity-discounts/{$id}", [
                'min_qty' => 20,
                'discount_percent' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('tier.discount_percent', '30.00');

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/product-quantity-discounts/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('product_quantity_discounts', ['id' => $id]);
    }

    public function test_store_validates_product_min_qty_and_discount_percent(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => 'nope',
                'min_qty' => 0,
                'discount_percent' => 101,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'min_qty', 'discount_percent']);

        $this->assertDatabaseCount('product_quantity_discounts', 0);
    }

    public function test_duplicate_product_min_qty_is_rejected(): void
    {
        $first = $this->actingAs($this->owner)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 10,
                'discount_percent' => 15,
            ])
            ->assertCreated()
            ->json('tier');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 10,
                'discount_percent' => 20,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('min_qty');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/product-quantity-discounts', [
                'product_id' => $this->product->id,
                'min_qty' => 20,
                'discount_percent' => 5,
            ])
            ->assertCreated();

        $this->actingAs($this->owner)
            ->putJson("/api/v1/product-quantity-discounts/{$first['id']}", [
                'min_qty' => 20,
                'discount_percent' => 25,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('min_qty');

        $this->assertDatabaseCount('product_quantity_discounts', 2);
    }

    public function test_manager_without_branch_is_denied_tier_read(): void
    {
        $this->actingAs($this->branchlessManager)
            ->getJson('/api/v1/product-quantity-discounts')
            ->assertForbidden();
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
