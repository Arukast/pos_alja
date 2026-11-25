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
        $branchTwoOrder = $this->order($invoiceTwo, 0);

        $this->materialOne = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => ProductCategory::create([
                'name' => 'Large Format',
                'calculation_archetype' => 'surface_area',
            ])->id,
            'type' => 'roll',
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

        $this->assertCount(2, $profileResponse->json('data'));

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/production/queue')
            ->assertOk();

        $this->assertCount(3, $ownerResponse->json('data'));

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/production/queue')
            ->assertOk()
            ->assertJsonMissingPath('data.0.unit_price');
    }

    public function test_summary_counts_match_fixtures_and_are_production_only(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->assertOk()
            ->assertJsonPath('queued', 1)
            ->assertJsonPath('processing', 1)
            ->assertJsonPath('completed_today', 1);

        $this->assertEquals(2, (float) $this->actingAs($this->production)
            ->getJson('/api/v1/production/summary')
            ->json('waste_today'));

        $this->actingAs($this->owner)
            ->getJson('/api/v1/production/summary')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/production/summary')
            ->assertForbidden();
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
        $this->assertNotNull($response->json('data.0.order_id'));

        foreach ([$this->owner, $this->manager, $this->cashier, $this->designer] as $user) {
            $this->actingAs($user)->getJson('/api/v1/waste')->assertForbidden();
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
        $this->assertSame([$this->designer->id], $designerIds);
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
