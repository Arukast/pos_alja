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

class MaterialMinStockApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private Material $material;

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

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
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

    public function test_owner_updates_minimum_stock_for_one_branch(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson($this->url(), [
                'branch_id' => $this->branchOne->id,
                'min_stock' => 4.5,
            ])
            ->assertOk();

        $updatedStock = collect($response->json('material.stocks'))
            ->firstWhere('branch_id', $this->branchOne->id);

        $this->assertNotNull($updatedStock);
        $this->assertSame(4.5, (float) $updatedStock['min_stock']);
        $this->assertSame(5.0, (float) $updatedStock['current_stock']);

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 4.5,
            'current_stock' => 5,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'min_stock' => 1,
            'current_stock' => 7,
        ]);
    }

    public function test_owner_must_send_a_minimum_stock_value(): void
    {
        $this->actingAs($this->owner)
            ->patchJson($this->url(), ['branch_id' => $this->branchOne->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_stock');

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
    }

    public function test_owner_must_name_a_branch_for_minimum_stock(): void
    {
        $this->actingAs($this->owner)
            ->patchJson($this->url(), ['min_stock' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
    }

    public function test_owner_minimum_stock_rejects_unknown_branch(): void
    {
        $this->actingAs($this->owner)
            ->patchJson($this->url(), [
                'branch_id' => (string) Str::uuid(),
                'min_stock' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_manager_minimum_stock_is_forced_to_the_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->patchJson($this->url(), ['min_stock' => 3])
            ->assertOk();

        $this->assertSame(3.0, (float) $response->json('material.min_stock'));

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 3,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'min_stock' => 1,
        ]);
    }

    public function test_manager_may_name_the_own_branch_explicitly(): void
    {
        $response = $this->actingAs($this->manager)
            ->patchJson($this->url(), [
                'branch_id' => $this->branchOne->id,
                'min_stock' => 3,
            ])
            ->assertOk();

        $this->assertSame(3.0, (float) $response->json('material.min_stock'));

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 3,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'min_stock' => 1,
        ]);
    }

    public function test_manager_cannot_target_another_branch_for_minimum_stock(): void
    {
        $this->actingAs($this->manager)
            ->patchJson($this->url(), [
                'branch_id' => $this->branchTwo->id,
                'min_stock' => 3,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'min_stock' => 1,
        ]);
    }

    public function test_branchless_manager_cannot_set_minimum_stock(): void
    {
        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->patchJson($this->url(), ['min_stock' => 3])
            ->assertForbidden();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
    }

    public function test_minimum_stock_requires_an_existing_branch_stock_row(): void
    {
        MaterialBranchStock::query()
            ->where('material_id', $this->material->id)
            ->where('branch_id', $this->branchTwo->id)
            ->delete();

        $this->assertDatabaseCount('material_branch_stock', 1);

        $this->actingAs($this->owner)
            ->patchJson($this->url(), [
                'branch_id' => $this->branchTwo->id,
                'min_stock' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');

        $this->assertDatabaseCount('material_branch_stock', 1);
    }

    public function test_minimum_stock_validation_errors_are_422(): void
    {
        $payloads = [
            ['min_stock' => -1],
            ['min_stock' => 'not-a-number'],
            ['min_stock' => 1.234],
            ['min_stock' => 100000000],
        ];

        foreach ($payloads as $payload) {
            $this->actingAs($this->manager)
                ->patchJson($this->url(), $payload)
                ->assertStatus(422)
                ->assertJsonValidationErrors('min_stock');
        }

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
    }

    public function test_minimum_stock_does_not_write_a_stock_ledger_row(): void
    {
        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->actingAs($this->manager)
            ->patchJson($this->url(), ['min_stock' => 3])
            ->assertOk();

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_other_roles_cannot_set_minimum_stock(): void
    {
        foreach ([$this->cashier, $this->designer, $this->production] as $actor) {
            $this->actingAs($actor)
                ->patchJson($this->url(), ['min_stock' => 3])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 1,
        ]);
    }

    public function test_guests_cannot_set_minimum_stock(): void
    {
        $this->patchJson($this->url(), ['min_stock' => 3])->assertUnauthorized();
    }

    public function test_minimum_stock_zero_is_accepted_and_flips_is_low_stock(): void
    {
        $this->actingAs($this->manager)
            ->patchJson($this->url(), ['min_stock' => 0])
            ->assertOk()
            ->assertJsonPath('material.is_low_stock', false);

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'min_stock' => 0,
            'current_stock' => 5,
        ]);

        $response = $this->actingAs($this->manager)
            ->patchJson($this->url(), ['min_stock' => 9])
            ->assertOk();

        $this->assertTrue($response->json('material.is_low_stock'));
        $this->assertSame(5.0, (float) $response->json('material.current_stock'));
        $this->assertSame(9.0, (float) $response->json('material.min_stock'));
    }

    private function url(): string
    {
        return "/api/v1/materials/{$this->material->id}/min-stock";
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
