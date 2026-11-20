<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Payment;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private User $otherDesigner;

    private Customer $customer;

    private Invoice $invoiceOne;

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
        $this->otherDesigner = $this->user('designer', $this->branchTwo);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $category->id,
            'type' => 'roll',
            'cost' => 10000,
            'price' => 15000,
        ]);

        $this->invoiceOne = $this->invoice($this->branchOne, $this->designer);
        $this->invoice($this->branchTwo, $this->otherDesigner);

        $this->payment($this->invoiceOne, $this->branchOne, 40000, 'Cash');
        $this->payment($this->invoiceOne, $this->branchOne, 30000, 'Transfer');
        $this->payment($this->invoiceOne, $this->branchOne, 30000, 'QRIS');

        Expense::create([
            'branch_id' => $this->branchOne->id,
            'incurred_at' => now()->toDateString(),
            'amount' => 10000,
            'description' => 'Paper',
            'cashier_name' => $this->cashier->name,
            'type' => 'laba',
            'category' => 'Supplies',
        ]);

        WastedMaterial::create([
            'branch_id' => $this->branchOne->id,
            'logged_at' => now(),
            'material_id' => $this->material->id,
            'designer_id' => $this->designer->id,
            'operator_id' => $this->production->id,
            'quantity' => 2,
            'notes' => 'Failed print',
        ]);
    }

    public function test_dashboard_returns_a_role_shaped_payload(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'owner')
            ->assertJsonStructure([
                'finance' => ['gross_revenue', 'total_cost', 'net_profit', 'expenses'],
                'sparkline' => ['points', 'labels'],
            ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'manager')
            ->assertJsonStructure(['counts', 'stock_ledgers', 'expenses', 'leaderboard']);

        $this->actingAs($this->cashier)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'cashier')
            ->assertJsonStructure(['stats', 'billing_queue', 'pickup_queue', 'expenses']);

        $this->actingAs($this->designer)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'designer')
            ->assertJsonStructure(['stats', 'recent_invoices']);

        $this->actingAs($this->production)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'production')
            ->assertJsonStructure(['queued', 'processing', 'completed_today', 'waste_today', 'queue'])
            ->assertJsonMissingPath('finance')
            ->assertJsonMissingPath('queue.data.0.unit_price')
            ->assertJsonMissingPath('queue.data.0.invoice.grand_total');
    }

    public function test_owner_gross_revenue_report_scopes_by_branch_and_period(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/gross-revenue?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('grand_total', '100000.00')
            ->assertJsonPath('cash_total', '40000.00')
            ->assertJsonPath('transfer_total', '30000.00')
            ->assertJsonPath('qris_total', '30000.00');

        $this->assertCount(3, $response->json('payments'));
    }

    public function test_manager_cannot_read_owner_reports(): void
    {
        foreach (['gross-revenue', 'base-cost', 'net-profit', 'expenses', 'sparkline'] as $report) {
            $this->actingAs($this->manager)
                ->getJson('/api/v1/reports/'.$report.'?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
                ->assertForbidden();
        }
    }

    public function test_owner_net_profit_subtracts_cost_waste_and_expenses(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/net-profit?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('gross_revenue', '100000.00')
            ->assertJsonPath('total_hpp', '80000.00')
            ->assertJsonPath('expenses_total', '10000.00')
            ->assertJsonPath('net_profit', '10000.00');

        $this->assertEquals(10.0, (float) $response->json('margin_rate'));
    }

    public function test_owner_base_cost_and_expense_reports(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/base-cost?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('total_hpp', '80000.00')
            ->assertJsonPath('invoice_cost_total', '60000.00')
            ->assertJsonPath('wasted_cost_total', '20000.00');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/expenses?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('expenses_total', '10000.00')
            ->assertJsonPath('by_category.Supplies', '10000.00');
    }

    public function test_sparkline_returns_period_shapes(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/sparkline?period=daily')
            ->assertOk()
            ->assertJsonCount(6, 'points')
            ->assertJsonCount(6, 'labels');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/sparkline?period=weekly')
            ->assertOk()
            ->assertJsonCount(7, 'points');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/sparkline?period=monthly')
            ->assertOk()
            ->assertJsonCount(4, 'points');
    }

    public function test_manager_leaderboard_is_branch_scoped(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/reports/designer-leaderboard')
            ->assertOk();

        $ids = collect($response->json('leaders'))->pluck('id')->all();

        $this->assertSame([$this->designer->id], $ids);
        $this->assertArrayHasKey('revenue', $response->json('leaders.0'));
        $this->assertArrayHasKey('order_count', $response->json('leaders.0'));
        $this->assertArrayNotHasKey('grand_total', $response->json('leaders.0'));
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

    private function invoice(Branch $branch, User $designer): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'designer_id' => $designer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 100000,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ]);
    }

    private function payment(Invoice $invoice, Branch $branch, int $amount, string $method): void
    {
        Payment::create([
            'branch_id' => $branch->id,
            'invoice_id' => $invoice->id,
            'user_id' => $this->cashier->id,
            'amount' => $amount,
            'payment_method' => $method,
            'type' => 'settlement',
            'cashier_name' => $this->cashier->name,
        ]);
    }
}
