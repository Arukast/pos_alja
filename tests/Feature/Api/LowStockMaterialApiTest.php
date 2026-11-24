<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LowStockMaterialApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $managerWithoutBranch;

    private User $cashier;

    private User $designer;

    private User $production;

    private Material $lowInBoth;

    private Material $lowInBranchTwo;

    private Material $healthy;

    private Material $singleRowLow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->managerWithoutBranch = $this->user('manager', null);
        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->designer = $this->user('designer', $this->branchOne);
        $this->production = $this->user('production', $this->branchOne);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->lowInBoth = $this->material('Alpha Material', $category);
        $this->lowInBranchTwo = $this->material('Beta Material', $category);
        $this->healthy = $this->material('Gamma Material', $category);
        $this->singleRowLow = $this->material('Delta Material', $category);

        $this->stock($this->lowInBoth, $this->branchOne, 1, 5);
        $this->stock($this->lowInBoth, $this->branchTwo, 0, 0);

        $this->stock($this->lowInBranchTwo, $this->branchOne, 10, 2);
        $this->stock($this->lowInBranchTwo, $this->branchTwo, 0, 0);

        $this->stock($this->healthy, $this->branchOne, 10, 2);
        $this->stock($this->healthy, $this->branchTwo, 9, 2);

        $this->stock($this->singleRowLow, $this->branchTwo, 0, 0);
    }

    public function test_guest_cannot_read_materials(): void
    {
        $this->getJson('/api/v1/materials?low_stock=1')->assertUnauthorized();
    }

    public function test_owner_all_low_stock_uses_or_semantics_and_nulls_the_top_level_flag(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?low_stock=1&branch_id=all')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $materials = collect($response->json('data'))->keyBy('name');

        $this->assertEqualsCanonicalizing(
            ['Alpha Material', 'Beta Material', 'Delta Material'],
            $materials->keys()->all()
        );

        foreach ($materials as $material) {
            $this->assertNull($material['is_low_stock'], $material['name']);
        }

        $alpha = $this->stockFlags($materials['Alpha Material']);
        $this->assertTrue($alpha[$this->branchOne->id]);
        $this->assertTrue($alpha[$this->branchTwo->id]);

        $beta = $this->stockFlags($materials['Beta Material']);
        $this->assertFalse($beta[$this->branchOne->id]);
        $this->assertTrue($beta[$this->branchTwo->id]);

        // Delta has a single stock row, but owner/all must not report a single-branch truth.
        $this->assertNull($materials['Delta Material']['is_low_stock']);
        $this->assertCount(1, $materials['Delta Material']['stocks']);
        $this->assertEquals(0, (float) $materials['Delta Material']['current_stock']);

        // An owner request without branch_id is the same all-branch view.
        $default = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?low_stock=1')
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            $materials->keys()->all(),
            collect($default->json('data'))->pluck('name')->all()
        );
    }

    public function test_owner_single_branch_gets_a_boolean_top_level_flag(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id='.$this->branchOne->id)
            ->assertOk();

        $materials = collect($response->json('data'))->keyBy('name');

        $this->assertArrayHasKey('Alpha Material', $materials->all());
        $this->assertTrue($materials['Alpha Material']['is_low_stock']);
        $this->assertFalse($materials['Beta Material']['is_low_stock']);
        $this->assertFalse($materials['Gamma Material']['is_low_stock']);
        $this->assertArrayNotHasKey('Delta Material', $materials->all());
        $this->assertEquals(1, (float) $materials['Alpha Material']['current_stock']);

        // A material low only in another branch is filtered out for this branch.
        $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?low_stock=1&branch_id='.$this->branchOne->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Material')
            ->assertJsonPath('data.0.is_low_stock', true);
    }

    public function test_manager_low_stock_is_forced_to_the_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/materials?low_stock=1&branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Material')
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonPath('data.0.stocks.0.branch_id', $this->branchOne->id)
            ->assertJsonCount(1, 'data.0.stocks');

        $this->assertEquals(1, (float) $response->json('data.0.current_stock'));
    }

    public function test_non_owner_without_branch_gets_an_empty_list(): void
    {
        foreach (['/api/v1/materials', '/api/v1/materials?low_stock=1'] as $path) {
            $this->actingAs($this->managerWithoutBranch)
                ->getJson($path)
                ->assertOk()
                ->assertJsonCount(0, 'data')
                ->assertJsonPath('meta.total', 0);
        }
    }

    public function test_production_gets_the_flag_without_money_fields(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/materials?low_stock=1&branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Material')
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonMissingPath('data.0.cost')
            ->assertJsonMissingPath('data.0.price');

        // The sent branch is ignored: the row and its stock come from branch one.
        $this->assertEquals(1, (float) $response->json('data.0.current_stock'));
        $this->assertIsBool($response->json('data.0.is_low_stock'));

        $plain = $this->actingAs($this->production)
            ->getJson('/api/v1/materials')
            ->assertOk();

        $materials = collect($plain->json('data'))->keyBy('name');

        $this->assertTrue($materials['Alpha Material']['is_low_stock']);
        $this->assertFalse($materials['Beta Material']['is_low_stock']);
        $this->assertFalse($materials['Gamma Material']['is_low_stock']);
    }

    public function test_zero_stock_with_zero_minimum_is_low(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?low_stock=1&branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $materials = collect($response->json('data'))->keyBy('name');

        $this->assertTrue($materials['Beta Material']['is_low_stock']);
        $this->assertEquals(0, (float) $materials['Beta Material']['current_stock']);
        $this->assertEquals(0, (float) $materials['Beta Material']['min_stock']);
    }

    public function test_every_stock_row_carries_a_boolean_flag(): void
    {
        $materials = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->json('data');

        foreach ($materials as $material) {
            $this->assertNull($material['is_low_stock']);
            $this->assertNotEmpty($material['stocks']);

            foreach ($material['stocks'] as $stock) {
                $this->assertArrayHasKey('is_low_stock', $stock);
                $this->assertIsBool($stock['is_low_stock']);
            }
        }
    }

    public function test_cashier_and_designer_cannot_read_low_stock_materials(): void
    {
        foreach ([$this->cashier, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson('/api/v1/materials?low_stock=1')
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

    private function material(string $name, ProductCategory $category): Material
    {
        return Material::create([
            'name' => $name,
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);
    }

    private function stock(Material $material, Branch $branch, float $currentStock, float $minStock): void
    {
        MaterialBranchStock::create([
            'material_id' => $material->id,
            'branch_id' => $branch->id,
            'current_stock' => $currentStock,
            'min_stock' => $minStock,
        ]);
    }

    /**
     * @param  array<string, mixed>  $material
     * @return array<string, bool>
     */
    private function stockFlags(array $material): array
    {
        return collect($material['stocks'])
            ->mapWithKeys(fn (array $stock): array => [$stock['branch_id'] => $stock['is_low_stock']])
            ->all();
    }
}
