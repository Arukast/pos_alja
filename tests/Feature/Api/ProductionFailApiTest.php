<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\OrderUpdated;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionFailApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $production;

    private User $secondProduction;

    private User $otherProduction;

    private Invoice $invoice;

    private Invoice $otherInvoice;

    private Material $material;

    private Pricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->production = $this->user('production', $this->branchOne);
        $this->secondProduction = $this->user('production', $this->branchOne);
        $this->otherProduction = $this->user('production', $this->branchTwo);

        $customer = Customer::create([
            'name' => 'Jane Customer',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);

        $product = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'unit' => 'm2',
            'cost' => 10000,
            'price' => 15000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 1,
        ]);

        $this->pricing = Pricing::create([
            'branch_id' => $this->branchOne->id,
            'product_id' => $product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 50000,
            'cost' => 10000,
            'profit' => 40000,
        ]);

        $this->invoice = $this->invoice($this->branchOne, $customer, 'INV-B1-001');
        $this->otherInvoice = $this->invoice($this->branchTwo, $customer, 'INV-B2-001');
    }

    public function test_guest_cannot_fail_or_reopen_orders(): void
    {
        $order = $this->order($this->invoice, 1);

        $this->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertUnauthorized();
        $this->postJson("/api/v1/orders/{$order->id}/reopen")
            ->assertUnauthorized();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 1]);
        $this->assertDatabaseCount('order_status_logs', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_only_production_can_fail_or_reopen_orders(): void
    {
        $owner = $this->user('owner', null);
        $manager = $this->user('manager', $this->branchOne);
        $cashier = $this->user('cashier', $this->branchOne);
        $designer = $this->user('designer', $this->branchOne);

        $processing = $this->order($this->invoice, 1);
        $failed = $this->order($this->invoice, 3);

        foreach ([$owner, $manager, $cashier, $designer] as $user) {
            $this->actingAs($user)
                ->postJson("/api/v1/orders/{$processing->id}/fail", ['note' => 'Bahan rusak'])
                ->assertForbidden();
            $this->actingAs($user)
                ->postJson("/api/v1/orders/{$failed->id}/reopen")
                ->assertForbidden();
        }

        $this->assertDatabaseHas('orders', ['id' => $processing->id, 'status' => 1]);
        $this->assertDatabaseHas('orders', ['id' => $failed->id, 'status' => 3]);
        $this->assertDatabaseCount('order_status_logs', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_production_cannot_fail_or_reopen_another_branch_order(): void
    {
        $otherProcessing = $this->order($this->otherInvoice, 1);
        $otherFailed = $this->order($this->otherInvoice, 3);
        $processing = $this->order($this->invoice, 1);
        $failed = $this->order($this->invoice, 3);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$otherProcessing->id}/fail", ['note' => 'Bahan rusak'])
            ->assertForbidden();
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$otherFailed->id}/reopen")
            ->assertForbidden();

        $this->actingAs($this->otherProduction)
            ->postJson("/api/v1/orders/{$processing->id}/fail", ['note' => 'Bahan rusak'])
            ->assertForbidden();
        $this->actingAs($this->otherProduction)
            ->postJson("/api/v1/orders/{$failed->id}/reopen")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $otherProcessing->id, 'status' => 1]);
        $this->assertDatabaseHas('orders', ['id' => $otherFailed->id, 'status' => 3]);
        $this->assertDatabaseCount('order_status_logs', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_fail_requires_a_processing_order(): void
    {
        foreach ([0, 2, 3] as $status) {
            $order = $this->order($this->invoice, $status);

            $this->actingAs($this->production)
                ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
                ->assertStatus(422)
                ->assertJsonValidationErrors('status');

            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $status]);
        }

        $this->assertDatabaseCount('order_status_logs', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_fail_requires_a_note_between_three_and_two_hundred_fifty_five_characters(): void
    {
        $order = $this->order($this->invoice, 1);

        foreach ([[], ['note' => str_repeat('a', 2)], ['note' => str_repeat('a', 256)]] as $payload) {
            $this->actingAs($this->production)
                ->postJson("/api/v1/orders/{$order->id}/fail", $payload)
                ->assertStatus(422)
                ->assertJsonValidationErrors('note');
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 1]);
        $this->assertDatabaseCount('order_status_logs', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_fail_accepts_a_note_at_the_length_boundaries(): void
    {
        foreach ([3, 255] as $length) {
            $note = str_repeat('a', $length);
            $order = $this->order($this->invoice, 1, $this->production->id);

            $this->actingAs($this->production)
                ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => $note])
                ->assertOk()
                ->assertJsonPath('order.status', 3);

            $this->assertDatabaseHas('order_status_logs', [
                'order_id' => $order->id,
                'status' => 3,
                'notes' => $note,
            ]);
        }
    }

    public function test_start_rejects_a_failed_order(): void
    {
        $order = $this->order($this->invoice, 3, $this->production->id);

        MaterialBranchStock::query()
            ->where('material_id', $this->material->id)
            ->where('branch_id', $this->branchOne->id)
            ->update(['current_stock' => 4]);

        $this->ledger($order, 'usage', used: 6, remaining: 4);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/start")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 3]);
        $this->assertSame(4.0, $this->currentStock());
        $this->assertSame(1, DB::table('stock_ledgers')->count());
    }

    public function test_fail_reverses_net_usage_and_keeps_the_operator(): void
    {
        $order = $this->order($this->invoice, 0);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/start")
            ->assertOk();

        $this->assertSame(4.0, $this->currentStock());

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->secondProduction)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk()
            ->assertJsonPath('order.status', 3)
            ->assertJsonPath('order.operator.id', $this->production->id);

        $this->assertMoneyFree($response->json('order'));

        $this->assertSame(10.0, $this->currentStock());
        $this->assertDatabaseHas('stock_ledgers', [
            'branch_id' => $this->branchOne->id,
            'material_id' => $this->material->id,
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'user_id' => $this->secondProduction->id,
            'added_stock' => 6,
            'used_stock' => 0,
            'remaining_stock' => 10,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 3,
            'operator_id' => $this->production->id,
        ]);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => 3,
            'user_id' => $this->secondProduction->id,
            'notes' => 'Bahan rusak',
        ]);

        Event::assertDispatched(
            OrderUpdated::class,
            fn (OrderUpdated $event) => $event->branchId === $this->branchOne->id
        );
    }

    public function test_fail_writes_order_fail_audit_with_reversal(): void
    {
        $order = $this->order($this->invoice, 0);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/start")
            ->assertOk();

        $this->actingAs($this->secondProduction)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk();

        $reversal = StockLedger::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage_reversal')
            ->firstOrFail();

        $log = ActivityLog::query()
            ->where('action', 'ORDER_FAIL')
            ->where('model_type', Order::class)
            ->where('model_id', $order->id)
            ->firstOrFail();

        $this->assertSame($this->secondProduction->id, $log->user_id);
        $this->assertSame($this->branchOne->id, $log->branch_id);
        $this->assertSame(1, $log->payload['status_before']);
        $this->assertSame(3, $log->payload['status_after']);
        $this->assertSame($this->production->id, $log->payload['operator_before']);
        $this->assertSame($this->production->id, $log->payload['operator_after']);
        $this->assertSame('Bahan rusak', $log->payload['note']);

        $reversals = $log->payload['material_usage'];
        $this->assertCount(1, $reversals);
        $this->assertSame($this->material->id, $reversals[0]['material_id']);
        $this->assertSame(6.0, (float) $reversals[0]['quantity']);
        $this->assertSame(4.0, (float) $reversals[0]['stock_before']);
        $this->assertSame(10.0, (float) $reversals[0]['stock_after']);
        $this->assertSame('reversal', $reversals[0]['direction']);
        $this->assertSame($reversal->id, $reversals[0]['ledger_id']);
    }

    public function test_fail_reverses_only_the_net_usage_after_a_prior_reversal(): void
    {
        $order = $this->order($this->invoice, 1, $this->production->id);

        $this->ledger($order, 'usage', used: 6, remaining: 4);
        $this->ledger($order, 'usage_reversal', added: 4, remaining: 8);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Cetak ulang'])
            ->assertOk()
            ->assertJsonPath('order.status', 3);

        $this->assertSame(12.0, $this->currentStock());
        $this->assertDatabaseHas('stock_ledgers', [
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'user_id' => $this->production->id,
            'added_stock' => 2,
            'notes' => 'Cetak ulang',
            'remaining_stock' => 12,
        ]);
        $this->assertSame(2, DB::table('stock_ledgers')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage_reversal')
            ->count());
    }

    public function test_fail_reverses_usage_per_material_group(): void
    {
        $secondMaterial = Material::create([
            'name' => 'Sticker Roll',
            'product_category_id' => $this->material->product_category_id,
            'type' => 'roll',
            'unit' => 'lembar',
        ]);

        MaterialBranchStock::create([
            'material_id' => $secondMaterial->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 1,
        ]);

        $order = $this->order($this->invoice, 1, $this->production->id);

        $this->ledger($order, 'usage', used: 6, remaining: 4);
        $this->ledger($order, 'usage', used: 3, remaining: 7, material: $secondMaterial);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk();

        $this->assertSame(16.0, $this->currentStock());
        $this->assertSame(13.0, (float) MaterialBranchStock::query()
            ->where('material_id', $secondMaterial->id)
            ->where('branch_id', $this->branchOne->id)
            ->value('current_stock'));
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->material->id,
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'added_stock' => 6,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $secondMaterial->id,
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'added_stock' => 3,
        ]);
        $this->assertSame(2, DB::table('stock_ledgers')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage_reversal')
            ->count());
    }

    public function test_fail_reverses_usage_groups_in_ascending_material_order(): void
    {
        $secondMaterial = Material::create([
            'name' => 'Sticker Roll',
            'product_category_id' => $this->material->product_category_id,
            'type' => 'roll',
            'unit' => 'lembar',
        ]);

        $materials = [$this->material, $secondMaterial];
        usort($materials, fn (Material $a, Material $b): int => $a->id <=> $b->id);

        $branches = [$this->branchOne, $this->branchTwo];
        usort($branches, fn (Branch $a, Branch $b): int => $a->id <=> $b->id);

        $order = $this->order($this->invoice, 1, $this->production->id);

        // Interleaved: the material order opposes the branch order, so the
        // grouped-but-unordered query would reverse the expected sequence.
        $this->ledger($order, 'usage', used: 6, remaining: 4, material: $materials[0], branch: $branches[1]);
        $this->ledger($order, 'usage', used: 3, remaining: 7, material: $materials[1], branch: $branches[0]);

        $inventory = new class extends InventoryService
        {
            /** @var list<array{0: string, 1: string}> */
            public array $movements = [];

            public function applyMovement(string $materialId, string $branchId, string $type, float $quantity, ?string $reason = null, ?string $referenceType = null, ?string $referenceId = null, ?string $notes = null, ?User $actor = null, bool $absolute = false): array
            {
                $this->movements[] = [$materialId, $branchId];

                return ['old_stock' => 0.0, 'new_stock' => 0.0, 'ledger_id' => (string) Str::uuid()];
            }
        };

        $this->app->instance(InventoryService::class, $inventory);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk();

        $this->assertSame([
            [$materials[0]->id, $branches[1]->id],
            [$materials[1]->id, $branches[0]->id],
        ], $inventory->movements);
    }

    public function test_fail_ignores_waste_ledger_rows_when_reversing(): void
    {
        $order = $this->order($this->invoice, 1, $this->production->id);

        $this->ledger($order, 'usage', used: 6, remaining: 4);
        $this->ledger($order, 'waste', used: 4, remaining: 0);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk();

        $this->assertSame(16.0, $this->currentStock());
        $this->assertDatabaseHas('stock_ledgers', [
            'type' => 'usage_reversal',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'added_stock' => 6,
        ]);
        $this->assertSame(1, DB::table('stock_ledgers')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage_reversal')
            ->count());
    }

    public function test_fail_without_net_usage_sets_status_and_writes_no_reversal(): void
    {
        $order = $this->order($this->invoice, 1, $this->production->id);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk()
            ->assertJsonPath('order.status', 3)
            ->assertJsonPath('order.has_waste', false);

        $this->assertSame(10.0, $this->currentStock());
        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => 3,
            'notes' => 'Bahan rusak',
        ]);
    }

    public function test_fail_reports_waste_already_recorded_for_the_order(): void
    {
        $order = $this->order($this->invoice, 1, $this->production->id);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $this->material->id,
            'order_id' => $order->id,
            'designer_id' => $this->production->id,
            'operator_id' => $this->production->id,
            'quantity' => 1,
        ]);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk()
            ->assertJsonPath('order.status', 3)
            ->assertJsonPath('order.has_waste', true);
    }

    public function test_reopen_restores_pending_and_clears_the_operator(): void
    {
        Event::fake([OrderUpdated::class]);

        $order = $this->order($this->invoice, 3, $this->production->id);

        $response = $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/reopen")
            ->assertOk()
            ->assertJsonPath('order.status', 0)
            ->assertJsonPath('order.operator', null);

        $this->assertMoneyFree($response->json('order'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 0,
            'operator_id' => null,
        ]);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => 0,
            'user_id' => $this->production->id,
            'notes' => 'Production reopened',
        ]);
        $this->assertDatabaseCount('stock_ledgers', 0);

        Event::assertDispatched(
            OrderUpdated::class,
            fn (OrderUpdated $event) => $event->branchId === $this->branchOne->id
        );
    }

    public function test_reopen_rejects_a_non_failed_order(): void
    {
        foreach ([0, 1, 2] as $status) {
            $order = $this->order($this->invoice, $status, $this->production->id);

            $this->actingAs($this->production)
                ->postJson("/api/v1/orders/{$order->id}/reopen")
                ->assertStatus(422)
                ->assertJsonValidationErrors('status');

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $status,
                'operator_id' => $this->production->id,
            ]);
        }

        $this->assertDatabaseCount('order_status_logs', 0);
    }

    public function test_reopen_then_start_deducts_again(): void
    {
        $order = $this->order($this->invoice, 0);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/start")
            ->assertOk();
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/fail", ['note' => 'Bahan rusak'])
            ->assertOk();
        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/reopen")
            ->assertOk()
            ->assertJsonPath('order.status', 0);

        $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$order->id}/start")
            ->assertOk()
            ->assertJsonPath('order.status', 1);

        $this->assertSame(4.0, $this->currentStock());
        $this->assertSame(2, DB::table('stock_ledgers')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage')
            ->count());
        $this->assertSame(12.0, (float) DB::table('stock_ledgers')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'usage')
            ->sum('used_stock'));
    }

    public function test_waste_is_allowed_on_a_failed_order(): void
    {
        $order = $this->order($this->invoice, 3, $this->production->id);

        $this->actingAs($this->production)
            ->postJson('/api/v1/waste', [
                'material_id' => $this->material->id,
                'order_id' => $order->id,
                'quantity' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 3]);
        $this->assertSame(9.0, $this->currentStock());
        $this->assertDatabaseHas('stock_ledgers', [
            'type' => 'waste',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'wasted_stock' => 1,
        ]);
    }

    private function currentStock(): float
    {
        return (float) MaterialBranchStock::query()
            ->where('material_id', $this->material->id)
            ->where('branch_id', $this->branchOne->id)
            ->value('current_stock');
    }

    private function ledger(Order $order, string $type, float $used = 0, float $added = 0, float $remaining = 0, ?Material $material = null, ?Branch $branch = null): void
    {
        StockLedger::create([
            'branch_id' => ($branch ?? $this->branchOne)->id,
            'material_id' => ($material ?? $this->material)->id,
            'type' => $type,
            'user_id' => $this->production->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'logged_at' => now(),
            'used_stock' => $used,
            'added_stock' => $added,
            'remaining_stock' => $remaining,
        ]);
    }

    private function assertMoneyFree(mixed $payload): void
    {
        if (! is_array($payload)) {
            return;
        }

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $this->assertDoesNotMatchRegularExpression(
                    '/price|cost|profit|discount|receivable|payment|total|subtotal|dp_amount|grand_total/i',
                    $key
                );
            }

            $this->assertMoneyFree($value);
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

    private function invoice(Branch $branch, Customer $customer, string $number): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'designer_id' => $this->user('designer', $branch)->id,
            'status' => 1,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 100000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ]);
    }

    private function order(Invoice $invoice, int $status, ?string $operatorId = null): Order
    {
        return Order::create([
            'invoice_id' => $invoice->id,
            'pricing_id' => $this->pricing->id,
            'operator_id' => $operatorId,
            'file_name' => 'job.pdf',
            'notes' => 'Production job',
            'area' => 3,
            'quantity' => 2,
            'unit_price' => 50000,
            'subtotal' => 100000,
            'cost' => 60000,
            'profit' => 40000,
            'ordered_at' => now()->toDateString(),
            'status' => $status,
            'pickup_status' => 0,
            'is_manual_price' => false,
        ]);
    }
}
