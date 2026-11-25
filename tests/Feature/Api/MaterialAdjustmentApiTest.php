<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialAdjustmentApiTest extends TestCase
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

    private Material $materialA;

    private Material $materialB;

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

        $this->materialA = Material::create([
            'name' => 'Material A',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);

        $this->materialB = Material::create([
            'name' => 'Material B',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 2000,
            'price' => 4000,
        ]);

        $this->stock($this->materialA, $this->branchOne, 10, 2);
    }

    public function test_guest_cannot_adjust_stock(): void
    {
        $this->postJson($this->url($this->materialA), $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_non_management_roles_cannot_adjust_stock(): void
    {
        foreach ([$this->cashier, $this->designer, $this->production] as $user) {
            $this->actingAs($user)
                ->postJson($this->url($this->materialA), $this->payload())
                ->assertForbidden();
        }

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_manager_branch_is_forced_and_a_sent_branch_is_ignored(): void
    {
        $this->stock($this->materialA, $this->branchTwo, 7, 1);

        $response = $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'branch_id' => $this->branchTwo->id,
                'new_quantity' => 8,
            ]))
            ->assertOk()
            ->assertJsonPath('material.stocks.0.branch_id', $this->branchOne->id);

        $this->assertEquals(8, (float) $response->json('material.current_stock'));

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
        ]);
        $this->assertDatabaseMissing('stock_ledgers', [
            'branch_id' => $this->branchTwo->id,
        ]);
    }

    public function test_manager_without_branch_gets_a_validation_error(): void
    {
        $this->actingAs($this->managerWithoutBranch)
            ->postJson($this->url($this->materialA), $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cabang wajib diisi.');

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_owner_must_send_a_branch(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url($this->materialA), $this->payload(['branch_id' => null]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cabang wajib diisi.');

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_owner_adjusts_a_selected_branch(): void
    {
        $this->stock($this->materialA, $this->branchTwo, 2, 1);

        $this->actingAs($this->owner)
            ->postJson($this->url($this->materialA), $this->payload([
                'branch_id' => $this->branchTwo->id,
                'new_quantity' => 7,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
        ]);
        $this->assertDatabaseCount('stock_ledgers', 1);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchTwo->id,
            'type' => 'adjustment',
            'user_id' => $this->owner->id,
            'initial_stock' => 2,
            'added_stock' => 5,
            'remaining_stock' => 7,
        ]);
        $this->assertDatabaseMissing('stock_ledgers', [
            'branch_id' => $this->branchOne->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'ADJUST',
            'branch_id' => $this->branchTwo->id,
            'user_id' => $this->owner->id,
            'model_type' => Material::class,
            'model_id' => $this->materialA->id,
        ]);
    }

    public function test_reason_is_required(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['reason' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStock(10);
    }

    public function test_new_quantity_adjustment_updates_stock_and_returns_only_the_material(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => 4]))
            ->assertOk()
            ->assertJsonPath('material.id', $this->materialA->id);

        $this->assertSame(['material'], array_keys($response->json()));
        $this->assertEquals(4, (float) $response->json('material.current_stock'));

        $this->assertStock(4);
        $this->assertDatabaseCount('stock_ledgers', 1);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'type' => 'adjustment',
            'initial_stock' => 10,
            'added_stock' => -6,
            'remaining_stock' => 4,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_delta_adjustment_applies_a_signed_change(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => 2.5,
            ]))
            ->assertOk();

        $this->assertStock(12.5);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => -4,
            ]))
            ->assertOk();

        $this->assertStock(8.5);
        $this->assertDatabaseCount('stock_ledgers', 2);
        $this->assertDatabaseHas('stock_ledgers', [
            'added_stock' => 2.5,
            'remaining_stock' => 12.5,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'added_stock' => -4,
            'remaining_stock' => 8.5,
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_new_quantity_zero_is_allowed(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => 0]))
            ->assertOk();

        $this->assertEquals(0, (float) $response->json('material.current_stock'));
        $this->assertStock(0);
        $this->assertDatabaseHas('stock_ledgers', [
            'added_stock' => -10,
            'remaining_stock' => 0,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_reason_length_must_be_between_three_and_two_hundred_fifty_five_characters(): void
    {
        foreach (['ab', str_repeat('a', 256)] as $reason) {
            $this->actingAs($this->manager)
                ->postJson($this->url($this->materialA), $this->payload(['reason' => $reason]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        }

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['reason' => 'abc']))
            ->assertOk();

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['reason' => str_repeat('a', 255)]))
            ->assertOk();

        $this->assertDatabaseCount('stock_ledgers', 2);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_exactly_one_adjustment_mode_is_required(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['delta' => 1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['new_quantity', 'delta']);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['new_quantity']);

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_zero_delta_is_rejected(): void
    {
        foreach ([0, '0.00'] as $delta) {
            $this->actingAs($this->manager)
                ->postJson($this->url($this->materialA), $this->payload([
                    'new_quantity' => null,
                    'delta' => $delta,
                ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['delta']);
        }

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_new_quantity_negative_and_over_cap_are_rejected(): void
    {
        foreach ([-1, 100000000] as $quantity) {
            $this->actingAs($this->manager)
                ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => $quantity]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['new_quantity']);
        }

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStock(10);
    }

    public function test_delta_below_zero_result_is_rejected_without_a_ledger_row(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => -10.01,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta']);

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStock(10);
    }

    public function test_resulting_stock_ceiling_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => 99999999.99,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta']);

        $this->setStock(99999999.99);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => 0.01,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['delta']);

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStock(99999999.99);
    }

    public function test_missing_stock_row_is_created_with_zero_minimum(): void
    {
        $this->assertDatabaseMissing('material_branch_stock', [
            'material_id' => $this->materialB->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialB), $this->payload(['new_quantity' => 4]))
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialB->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 4,
            'min_stock' => 0,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialB->id,
            'type' => 'adjustment',
            'initial_stock' => 0,
            'added_stock' => 4,
            'remaining_stock' => 4,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_adjustment_writes_a_typed_ledger_row_and_an_activity_log(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => -3.5,
                'reason' => 'Koreksi stok',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'type' => 'adjustment',
            'reason' => 'Koreksi stok',
            'user_id' => $this->manager->id,
            'initial_stock' => 10,
            'added_stock' => -3.5,
            'remaining_stock' => 6.5,
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $log = ActivityLog::query()->where('action', 'ADJUST')->firstOrFail();

        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertSame($this->branchOne->id, $log->branch_id);
        $this->assertSame($this->manager->id, $log->user_id);
        $this->assertSame($this->manager->username, $log->username);
        $this->assertSame('manager', $log->role);
        $this->assertSame(Material::class, $log->model_type);
        $this->assertSame($this->materialA->id, $log->model_id);
        $this->assertEquals(10, (float) $log->payload['before']);
        $this->assertEquals(6.5, (float) $log->payload['after']);
        $this->assertEquals(-3.5, (float) $log->payload['delta']);
        $this->assertSame('Koreksi stok', $log->payload['reason']);
    }

    public function test_malformed_numeric_strings_are_rejected(): void
    {
        foreach (['.5', '5.', '+5', '5.555', 'abc'] as $value) {
            $this->actingAs($this->manager)
                ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => $value]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['new_quantity']);
        }

        foreach (['.5', '5.', '+5', '--5', '-'] as $value) {
            $this->actingAs($this->manager)
                ->postJson($this->url($this->materialA), $this->payload([
                    'new_quantity' => null,
                    'delta' => $value,
                ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['delta']);
        }

        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStock(10);
    }

    public function test_valid_numeric_boundaries_are_accepted(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload(['new_quantity' => '99999999.99']))
            ->assertOk();
        $this->assertStock(99999999.99);

        $this->setStock(10);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => '-5',
            ]))
            ->assertOk();
        $this->assertStock(5);

        $this->setStock(10);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => '-5.5',
            ]))
            ->assertOk();
        $this->assertStock(4.5);

        $this->setStock(10);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => '0.01',
            ]))
            ->assertOk();
        $this->assertStock(10.01);

        $this->setStock(0);

        $this->actingAs($this->manager)
            ->postJson($this->url($this->materialA), $this->payload([
                'new_quantity' => null,
                'delta' => '99999999.99',
            ]))
            ->assertOk();
        $this->assertStock(99999999.99);
        $this->assertDatabaseCount('activity_logs', 5);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branchOne->id,
            'new_quantity' => 5,
            'reason' => 'Stock correction',
        ], $overrides);
    }

    private function url(Material $material): string
    {
        return "/api/v1/materials/{$material->id}/adjustments";
    }

    private function assertStock(float $expected): void
    {
        $stock = MaterialBranchStock::query()
            ->where('material_id', $this->materialA->id)
            ->where('branch_id', $this->branchOne->id)
            ->firstOrFail();

        $this->assertEquals($expected, (float) $stock->current_stock);
    }

    private function setStock(float $value): void
    {
        MaterialBranchStock::query()
            ->where('material_id', $this->materialA->id)
            ->where('branch_id', $this->branchOne->id)
            ->update(['current_stock' => $value]);
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

    private function stock(Material $material, Branch $branch, float $current, float $minimum): MaterialBranchStock
    {
        return MaterialBranchStock::create([
            'material_id' => $material->id,
            'branch_id' => $branch->id,
            'current_stock' => $current,
            'min_stock' => $minimum,
        ]);
    }
}
