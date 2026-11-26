<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialRetirementApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
        ]);
    }

    public function test_material_and_branch_stock_active_flags_default_true(): void
    {
        $this->assertTrue(Schema::hasColumn('materials', 'is_active'));
        $this->assertTrue(Schema::hasColumn('material_branch_stock', 'is_active'));

        $material = $this->material();
        $stock = $this->stock($material, $this->branchOne);

        $freshMaterial = $material->fresh();
        $freshStock = $stock->fresh();

        $this->assertIsBool($freshMaterial->is_active);
        $this->assertTrue($freshMaterial->is_active);
        $this->assertIsBool($freshStock->is_active);
        $this->assertTrue($freshStock->is_active);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'is_active' => true]);
        $this->assertDatabaseHas('material_branch_stock', ['id' => $stock->id, 'is_active' => true]);
    }

    public function test_material_list_exposes_global_and_branch_active_flags(): void
    {
        $material = $this->material();
        $this->stock($material, $this->branchOne);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk()
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.stocks.0.is_active', true);
    }

    public function test_manager_material_list_scopes_stocks_to_the_own_branch(): void
    {
        $material = $this->material();
        $this->stock($material, $this->branchOne);
        $this->stock($material, $this->branchTwo);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/materials')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.stocks')
            ->assertJsonPath('data.0.stocks.0.branch_id', $this->branchOne->id)
            ->assertJsonPath('data.0.stocks.0.is_active', true);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function material(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'name' => 'Vinyl Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ], $overrides));
    }

    private function stock(
        Material $material,
        Branch $branch,
        float $current = 5,
        float $min = 1,
    ): MaterialBranchStock {
        return $material->branchStocks()->create([
            'branch_id' => $branch->id,
            'current_stock' => $current,
            'min_stock' => $min,
        ]);
    }
}
