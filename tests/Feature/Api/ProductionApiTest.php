<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private User $otherProduction;

    private Material $materialOne;

    private Material $materialTwo;

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
        $this->otherProduction = $this->user('production', $this->branchTwo);

        $customer = Customer::create(['name' => 'Jane Customer', 'type' => 'Reguler']);

        $invoiceOne = $this->invoice($this->branchOne, $customer, 'INV-B1-001');
        $invoiceTwo = $this->invoice($this->branchTwo, $customer, 'INV-B2-001');

        $branchOneOrder = $this->order($invoiceOne, 0);
        $this->order($invoiceOne, 1);
        $this->order($invoiceOne, 2);
        $this->order($invoiceOne, 3);
        $branchTwoOrder = $this->order($invoiceTwo, 0);
        $this->order($invoiceTwo, 3);

        $this->materialOne = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => ProductCategory::create([
                'name' => 'Large Format',
                'calculation_archetype' => 'surface_area',
            ])->id,
            'type' => 'roll',
            'unit' => 'm2',
            'cost' => 10000,
            'price' => 15000,
        ]);

        $this->materialTwo = Material::create([
            'name' => 'Paper Roll',
            'product_category_id' => $this->materialOne->product_category_id,
            'type' => 'roll',
            'cost' => 5000,
            'price' => 7000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->materialOne->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
            'min_stock' => 2,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->materialTwo->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 4,
            'min_stock' => 1,
        ]);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $this->materialOne->id,
            'order_id' => $branchOneOrder->id,
            'designer_id' => $this->designer->id,
            'operator_id' => $this->production->id,
            'quantity' => 2,
            'dimensions' => '1.2m',
            'notes' => 'Failed print',
        ]);

        WastedMaterial::create([
            'branch_id' => $this->branchTwo->id,
            'logged_at' => now(),
            'material_id' => $this->materialTwo->id,
            'order_id' => $branchTwoOrder->id,
            'designer_id' => $this->otherProduction->id,
            'operator_id' => $this->otherProduction->id,
            'quantity' => 5,
            'notes' => 'Other branch waste',
        ]);
    }

    public function test_queue_is_branch_scoped_and_has_no_money(): void
    {
        $profileResponse = $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')
            ->assertOk()
            ->assertJsonMissingPath('data.0.unit_price')
            ->assertJsonMissingPath('data.0.subtotal')
            ->assertJsonMissingPath('data.0.cost')
            ->assertJsonMissingPath('data.0.profit')
            ->assertJsonMissingPath('data.0.invoice.grand_total');

        $this->assertCount(3, $profileResponse->json('data'));
        $this->assertContains(3, collect($profileResponse->json('data'))->pluck('status')->all());
        $this->assertMoneyFree($profileResponse->json('data'));

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $this->assertCount(5, $ownerResponse->json('data'));

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/production/queue')
            ->assertOk()
            ->assertJsonMissingPath('data.0.unit_price');
    }

    public function test_queue_filters_by_status_and_rejects_unknown_status(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?status=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 0);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?status=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 1);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?status=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 3);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/production/queue?status=3')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?status=2')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?status=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_queue_hides_orders_on_unpaid_invoices_for_branch_and_owner_all(): void
    {
        $customer = Customer::create(['name' => 'Unpaid Customer', 'type' => 'Reguler']);

        $unpaidOne = $this->invoice($this->branchOne, $customer, 'INV-B1-UNPAID');
        $unpaidOne->update(['status' => 0]);
        $unpaidOrderOne = $this->order($unpaidOne, 0);

        $unpaidTwo = $this->invoice($this->branchTwo, $customer, 'INV-B2-UNPAID');
        $unpaidTwo->update(['status' => 0]);
        $unpaidOrderTwo = $this->order($unpaidTwo, 0);

        $branchIds = collect($this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertCount(3, $branchIds);
        $this->assertNotContains($unpaidOrderOne->id, $branchIds);

        $paidBranchTwoId = Order::query()
            ->whereHas('invoice', fn ($query) => $query->where('branch_id', $this->branchTwo->id)->where('status', 1))
            ->value('id');
        $this->assertNotContains($paidBranchTwoId, $branchIds);

        $ownerIds = collect($this->actingAs($this->owner)
            ->getJson('/api/v1/production/queue')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertCount(5, $ownerIds);
        $this->assertNotContains($unpaidOrderOne->id, $ownerIds);
        $this->assertNotContains($unpaidOrderTwo->id, $ownerIds);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/production/queue?branch_id=all')
            ->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_summary_counts_exclude_orders_on_unpaid_invoices(): void
    {
        $customer = Customer::create(['name' => 'Unpaid Customer', 'type' => 'Reguler']);

        $unpaidOne = $this->invoice($this->branchOne, $customer, 'INV-B1-UNPAID');
        $unpaidOne->update(['status' => 0]);
        foreach ([0, 1, 2, 3] as $status) {
            $this->order($unpaidOne, $status);
        }

        $unpaidTwo = $this->invoice($this->branchTwo, $customer, 'INV-B2-UNPAID');
        $unpaidTwo->update(['status' => 0]);
        $this->order($unpaidTwo, 0);
        $this->order($unpaidTwo, 3);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('processing', 1)
            ->assertJsonPath('completed_today', 1)
            ->assertJsonPath('failed', 1);

        $this->actingAs($this->otherProduction)
            ->getJson('/api/v1/production/summary')->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('failed', 1);
    }

    public function test_queue_searches_invoice_number_and_file_name(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search=INV-B1-001')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search=job-1.pdf')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 1);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search=job-3.pdf&status=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 3);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search=no-such-job')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_queue_search_guards_the_uuid_id_column_from_non_uuid_text(): void
    {
        $orderId = (string) Order::query()->where('file_name', 'job-1.pdf')->value('id');

        $this->assertNotSame('', $orderId);

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search=not-a-uuid')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue?search='.$orderId)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderId);
    }

    public function test_summary_counts_match_fixtures_and_are_production_only(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('processing', 1)
            ->assertJsonPath('completed_today', 1)
            ->assertJsonPath('failed', 1)
            ->assertJsonPath('waste_today_by_unit.0.unit', 'm2')
            ->assertJsonMissingPath('waste_today');

        $this->assertEquals([['unit' => 'm2', 'quantity' => 2]], $response->json('waste_today_by_unit'));

        $otherResponse = $this->actingAs($this->otherProduction)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('processing', 0)
            ->assertJsonPath('completed_today', 0)
            ->assertJsonPath('failed', 1);

        $this->assertEquals([['unit' => 'pcs', 'quantity' => 5]], $otherResponse->json('waste_today_by_unit'));

        $this->actingAs($this->owner)
            ->getJson('/api/v1/production/summary')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/production/summary')
            ->assertForbidden();
    }

    public function test_waste_kpi_groups_by_unit_and_keeps_soft_deleted_units(): void
    {
        $lembar = Material::create([
            'name' => 'Sticker Sheet',
            'product_category_id' => $this->materialOne->product_category_id,
            'type' => 'sheet',
            'unit' => 'lembar',
            'cost' => 1000,
            'price' => 2000,
        ]);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $lembar->id,
            'designer_id' => $this->designer->id,
            'operator_id' => $this->production->id,
            'quantity' => 1.5,
            'notes' => 'Trim waste',
        ]);

        $expected = [
            ['unit' => 'lembar', 'quantity' => 1.5],
            ['unit' => 'm2', 'quantity' => 2],
        ];

        $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->assertJsonPath('waste_today_by_unit.0.unit', 'lembar')
            ->assertJsonPath('waste_today_by_unit.1.unit', 'm2');

        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->assertJsonMissingPath('waste_today');

        $this->assertEquals($expected, $response->json('waste_today_by_unit'));

        $this->materialOne->delete();

        $this->assertEquals($expected, $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->json('waste_today_by_unit'));
    }

    public function test_waste_list_is_branch_scoped_and_production_only(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/waste')
            ->assertOk()
            ->assertJsonMissingPath('data.0.cost')
            ->assertJsonMissingPath('data.0.machine');

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($this->materialOne->id, $response->json('data.0.material.id'));
        $this->assertSame('m2', $response->json('data.0.material.unit'));
        $this->assertNotNull($response->json('data.0.order_id'));

        foreach ([$this->owner, $this->manager, $this->cashier, $this->designer] as $user) {
            $this->actingAs($user)->getJson('/api/v1/waste')->assertForbidden();
        }
    }

    public function test_waste_list_returns_a_null_material_when_it_is_soft_deleted(): void
    {
        $this->materialOne->delete();

        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/waste')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertNull($response->json('data.0.material'));
    }

    public function test_queue_marks_a_failed_order_without_waste_as_has_waste_false(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $failed = collect($response->json('data'))->firstWhere('status', 3);

        $this->assertNotNull($failed);
        $this->assertArrayHasKey('has_waste', $failed);
        $this->assertFalse($failed['has_waste']);
    }

    public function test_queue_marks_has_waste_true_after_waste_is_recorded_for_the_failed_order(): void
    {
        $queue = $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $failed = collect($queue->json('data'))->firstWhere('status', 3);

        $this->assertNotNull($failed);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $this->materialOne->id,
            'order_id' => $failed['id'],
            'designer_id' => $this->designer->id,
            'operator_id' => $this->production->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $failed['id']);

        $this->assertNotNull($row);
        $this->assertTrue($row['has_waste']);
    }

    public function test_waste_on_a_non_failed_order_does_not_mark_other_orders(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $rows = collect($response->json('data'));

        foreach ([0 => true, 1 => false, 3 => false] as $status => $hasWaste) {
            $row = $rows->firstWhere('status', $status);

            $this->assertNotNull($row);
            $this->assertSame($hasWaste, $row['has_waste']);
        }
    }

    public function test_waste_options_exclude_money_and_ignore_cross_branch_filter(): void
    {
        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/waste/options?branch_id='.$this->branchTwo->id)
            ->assertOk()
            ->assertJsonMissingPath('materials.0.cost')
            ->assertJsonMissingPath('materials.0.price')
            ->assertJsonMissingPath('machines');

        $materialIds = collect($response->json('materials'))->pluck('id')->all();
        $designerIds = collect($response->json('designers'))->pluck('id')->all();

        $this->assertSame([$this->materialOne->id], $materialIds);
        $this->assertSame('m2', $response->json('materials.0.unit'));
        $this->assertSame([$this->designer->id], $designerIds);
    }

    public function test_waste_options_exclude_inactive_designers(): void
    {
        $this->designer->update(['is_active' => false]);

        $response = $this->actingAs($this->production)
            ->getJson('/api/v1/waste/options')
            ->assertOk();

        $this->assertNotContains(
            $this->designer->id,
            collect($response->json('designers'))->pluck('id')->all()
        );
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
            'designer_id' => $this->designer->id,
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

    private function order(Invoice $invoice, int $status): Order
    {
        return Order::create([
            'invoice_id' => $invoice->id,
            'file_name' => 'job-'.$status.'.pdf',
            'notes' => 'Production job',
            'area' => 2,
            'quantity' => 1,
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
