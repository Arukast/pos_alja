<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
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

        $this->stock($this->materialA, $this->branchOne, 5, 1);
        $this->stock($this->materialB, $this->branchOne, 0, 0);
    }

    public function test_guest_cannot_list_or_record_purchases(): void
    {
        $this->getJson('/api/v1/purchases')->assertUnauthorized();
        $this->postJson('/api/v1/purchases', $this->payload())->assertUnauthorized();
    }

    public function test_cashier_designer_and_production_cannot_list_or_record_purchases(): void
    {
        foreach ([$this->cashier, $this->designer, $this->production] as $user) {
            $this->actingAs($user)->getJson('/api/v1/purchases')->assertForbidden();
            $this->actingAs($user)->postJson('/api/v1/purchases', $this->payload())->assertForbidden();
        }
    }

    public function test_manager_without_branch_cannot_list_or_record_purchases(): void
    {
        $this->actingAs($this->managerWithoutBranch)
            ->getJson('/api/v1/purchases')
            ->assertForbidden();

        $this->actingAs($this->managerWithoutBranch)
            ->postJson('/api/v1/purchases', $this->payload())
            ->assertForbidden();
    }

    public function test_manager_only_lists_own_branch_purchases(): void
    {
        $this->seedPurchase($this->branchOne, $this->manager, 'Branch One Supplier');
        $this->seedPurchase($this->branchTwo, $this->owner, 'Branch Two Supplier');

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/purchases')
            ->assertOk();

        $suppliers = collect($response->json('data'))->pluck('supplier')->all();

        $this->assertSame(['Branch One Supplier'], $suppliers);
    }

    public function test_manager_branch_filter_is_ignored(): void
    {
        $this->seedPurchase($this->branchOne, $this->manager, 'Branch One Supplier');
        $this->seedPurchase($this->branchTwo, $this->owner, 'Branch Two Supplier');

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/purchases?branch_id='.$this->branchTwo->id)
            ->assertOk();

        $suppliers = collect($response->json('data'))->pluck('supplier')->all();

        $this->assertSame(['Branch One Supplier'], $suppliers);
    }

    public function test_owner_lists_all_branches_and_can_filter_one(): void
    {
        $this->seedPurchase($this->branchOne, $this->manager, 'Branch One Supplier');
        $this->seedPurchase($this->branchTwo, $this->owner, 'Branch Two Supplier');

        $all = $this->actingAs($this->owner)
            ->getJson('/api/v1/purchases')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->assertCount(2, $all->json('data'));

        $filtered = $this->actingAs($this->owner)
            ->getJson('/api/v1/purchases?branch_id='.$this->branchTwo->id)
            ->assertOk();

        $this->assertSame(['Branch Two Supplier'], collect($filtered->json('data'))->pluck('supplier')->all());
    }

    public function test_manager_branch_is_forced_when_recording_a_purchase(): void
    {
        $payload = $this->payload(['branch_id' => $this->branchTwo->id]);

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated();

        $response->assertJsonPath('purchase.branch.id', $this->branchOne->id);

        $this->assertDatabaseHas('purchases', [
            'id' => $response->json('purchase.id'),
            'branch_id' => $this->branchOne->id,
            'user_id' => $this->manager->id,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
        $this->assertDatabaseMissing('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchTwo->id,
        ]);
    }

    public function test_owner_must_send_a_branch_when_recording_a_purchase(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $this->payload(['branch_id' => null]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cabang wajib diisi.');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_validation_rejects_bad_payloads(): void
    {
        $tooManyItems = [];

        for ($i = 0; $i < 51; $i++) {
            $tooManyItems[] = [
                'material_id' => $this->materialA->id,
                'quantity' => 1,
                'unit_cost' => 1000,
            ];
        }

        $cases = [
            [['items' => []], 'items'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 0, 'unit_cost' => 1000]]], 'items.0.quantity'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1000000, 'unit_cost' => 1000]]], 'items.0.quantity'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => -1]]], 'items.0.unit_cost'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => 10000000]]], 'items.0.unit_cost'],
            [['items' => [['material_id' => (string) Str::uuid(), 'quantity' => 1, 'unit_cost' => 1000]]], 'items.0.material_id'],
            [['purchased_at' => now()->addDay()->toIso8601String()], 'purchased_at'],
            [['supplier' => str_repeat('a', 256)], 'supplier'],
            [['notes' => str_repeat('a', 1001)], 'notes'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => 1000.555]]], 'items.0.unit_cost'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => '.5', 'unit_cost' => 1000]]], 'items.0.quantity'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => '5.', 'unit_cost' => 1000]]], 'items.0.quantity'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => '+5', 'unit_cost' => 1000]]], 'items.0.quantity'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => '.5']]], 'items.0.unit_cost'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => '5.']]], 'items.0.unit_cost'],
            [['items' => [['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => '+5']]], 'items.0.unit_cost'],
            [['idempotency_key' => 'not-a-uuid'], 'idempotency_key'],
        ];

        foreach ($cases as [$overrides, $key]) {
            $this->actingAs($this->owner)
                ->postJson('/api/v1/purchases', $this->payload($overrides))
                ->assertStatus(422)
                ->assertJsonValidationErrors([$key]);
        }

        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $this->payload(['items' => $tooManyItems]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $this->payload(['items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 1.005, 'unit_cost' => 1000],
            ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $this->payload(['items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => 1000.555],
            ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.unit_cost']);
    }

    public function test_purchase_rejects_a_soft_deleted_material(): void
    {
        $this->materialA->delete();

        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.material_id']);

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_purchase_records_items_stock_movements_and_server_totals(): void
    {
        $payload = $this->payload([
            'total_amount' => '1.00',
            'items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 3, 'unit_cost' => 1000, 'subtotal' => '1.00'],
                ['material_id' => $this->materialB->id, 'quantity' => 1.5, 'unit_cost' => 2000.5],
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated()
            ->assertJsonPath('purchase.total_amount', '6000.75')
            ->assertJsonPath('purchase.supplier', 'Supplier A')
            ->assertJsonPath('purchase.user.id', $this->manager->id)
            ->assertJsonPath('purchase.items.0.material.id', $this->materialA->id)
            ->assertJsonPath('purchase.items.0.quantity', '3.00')
            ->assertJsonPath('purchase.items.0.unit_cost', '1000.00')
            ->assertJsonPath('purchase.items.0.subtotal', '3000.00')
            ->assertJsonPath('purchase.items.1.material.id', $this->materialB->id)
            ->assertJsonPath('purchase.items.1.quantity', '1.50')
            ->assertJsonPath('purchase.items.1.unit_cost', '2000.50')
            ->assertJsonPath('purchase.items.1.subtotal', '3000.75');

        $purchaseId = $response->json('purchase.id');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'branch_id' => $this->branchOne->id,
            'total_amount' => 6000.75,
        ]);
        $this->assertDatabaseCount('purchase_items', 2);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialB->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 1.5,
        ]);
        $this->assertDatabaseCount('stock_ledgers', 2);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'type' => 'purchase',
            'reference_type' => 'purchase',
            'reference_id' => $purchaseId,
            'user_id' => $this->manager->id,
            'added_stock' => 3,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->materialB->id,
            'type' => 'purchase',
            'reference_id' => $purchaseId,
            'added_stock' => 1.5,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'branch_id' => $this->branchOne->id,
            'user_id' => $this->manager->id,
            'username' => $this->manager->username,
            'role' => 'manager',
            'action' => 'CREATE',
            'description' => 'Mencatat pembelian bahan',
            'model_type' => Purchase::class,
            'model_id' => $purchaseId,
        ]);
    }

    public function test_line_subtotals_are_rounded_then_summed(): void
    {
        $payload = $this->payload([
            'items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 2.5, 'unit_cost' => 333.33],
                ['material_id' => $this->materialB->id, 'quantity' => 2.5, 'unit_cost' => 333.33],
            ],
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated()
            ->assertJsonPath('purchase.items.0.subtotal', '833.33')
            ->assertJsonPath('purchase.items.1.subtotal', '833.33')
            ->assertJsonPath('purchase.total_amount', '1666.66');
    }

    public function test_missing_branch_stock_row_is_created_with_zero_minimum(): void
    {
        $this->materialB->stocks()->delete();

        $payload = $this->payload([
            'items' => [
                ['material_id' => $this->materialB->id, 'quantity' => 2, 'unit_cost' => 500],
            ],
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialB->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 2,
            'min_stock' => 0,
        ]);
    }

    public function test_purchase_rejects_a_stock_ceiling_overflow(): void
    {
        MaterialBranchStock::query()
            ->where('material_id', $this->materialA->id)
            ->where('branch_id', $this->branchOne->id)
            ->update(['current_stock' => 99999999.99]);

        $payload = $this->payload([
            'items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 0.01, 'unit_cost' => 1000],
            ],
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 99999999.99,
        ]);
    }

    public function test_duplicate_material_lines_are_allowed_and_each_line_increments_stock(): void
    {
        $payload = $this->payload([
            'items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 1, 'unit_cost' => 1000],
                ['material_id' => $this->materialA->id, 'quantity' => 2, 'unit_cost' => 1000],
            ],
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated()
            ->assertJsonPath('purchase.total_amount', '3000.00');

        $this->assertDatabaseCount('purchase_items', 2);
        $this->assertDatabaseCount('stock_ledgers', 2);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
    }

    public function test_replaying_the_same_key_returns_the_stored_purchase_without_new_stock_or_ledger_rows(): void
    {
        $payload = $this->payload();

        $first = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated();

        $replay = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $payload)
            ->assertOk();

        $replay->assertJsonPath('purchase.id', $first->json('purchase.id'));

        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('purchase_items', 1);
        $this->assertDatabaseCount('stock_ledgers', 1);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialA->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
    }

    public function test_replaying_the_same_key_with_a_different_payload_returns_the_stored_purchase(): void
    {
        $first = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $this->payload([
                'idempotency_key' => $this->fixedKey(),
                'supplier' => 'Original Supplier',
            ]))
            ->assertCreated();

        $replay = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $this->payload([
                'idempotency_key' => $this->fixedKey(),
                'supplier' => 'Different Supplier',
                'items' => [
                    ['material_id' => $this->materialB->id, 'quantity' => 9, 'unit_cost' => 9000],
                ],
            ]))
            ->assertOk();

        $replay->assertJsonPath('purchase.id', $first->json('purchase.id'));
        $replay->assertJsonPath('purchase.supplier', 'Original Supplier');
        $replay->assertJsonPath('purchase.total_amount', '3000.00');

        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('purchase_items', 1);
        $this->assertDatabaseCount('stock_ledgers', 1);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->materialB->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 0,
        ]);
    }

    public function test_replay_lookup_is_scoped_to_the_resolved_branch(): void
    {
        $this->stock($this->materialA, $this->branchTwo, 0, 0);

        $payload = $this->payload([
            'idempotency_key' => $this->fixedKey(),
            'branch_id' => $this->branchOne->id,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $payload)
            ->assertCreated();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/purchases', $this->payload([
                'idempotency_key' => $this->fixedKey(),
                'branch_id' => $this->branchTwo->id,
            ]))
            ->assertCreated();

        $this->assertDatabaseCount('purchases', 2);
        $this->assertDatabaseCount('stock_ledgers', 2);
    }

    public function test_purchase_history_keeps_a_soft_deleted_material_name(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $this->payload())
            ->assertCreated();

        $this->materialA->delete();

        $this->actingAs($this->manager)
            ->getJson('/api/v1/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.material.name', 'Material A');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branchOne->id,
            'purchased_at' => now()->toIso8601String(),
            'supplier' => 'Supplier A',
            'notes' => 'Restock for the week',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [
                ['material_id' => $this->materialA->id, 'quantity' => 3, 'unit_cost' => 1000],
            ],
        ], $overrides);
    }

    private function fixedKey(): string
    {
        return '3f2504e0-4f89-41d3-9a0c-0305e82c3301';
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

    private function seedPurchase(Branch $branch, User $user, string $supplier): Purchase
    {
        return Purchase::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'purchased_at' => now(),
            'supplier' => $supplier,
            'total_amount' => '100.00',
            'notes' => null,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }
}
