<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Order;
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
            'unit' => 'm2',
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
            ->assertJsonPath('period', 'daily')
            ->assertJsonStructure([
                'finance' => ['gross_revenue', 'total_cost', 'net_profit', 'expenses', 'receivables'],
                'order_count',
                'production' => ['queued', 'processing', 'completed', 'failed'],
                'sparkline' => ['points', 'labels'],
            ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'manager')
            ->assertJsonPath('period', 'daily')
            ->assertJsonStructure([
                'finance' => ['gross_revenue', 'expenses', 'receivables'],
                'order_count',
                'production' => ['queued', 'processing', 'completed', 'failed'],
                'counts',
                'stock_ledgers',
                'expenses',
                'leaderboard',
            ]);

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
            ->assertJsonStructure(['queued', 'processing', 'completed_today', 'waste_today_by_unit', 'queue'])
            ->assertJsonMissingPath('finance')
            ->assertJsonMissingPath('queue.data.0.unit_price')
            ->assertJsonMissingPath('queue.data.0.invoice.grand_total');
    }

    public function test_dashboard_requires_authentication_and_validates_period(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized();

        $this->actingAs($this->owner)
            ->getJson('/api/v1/dashboard?period=yearly')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    }

    public function test_dashboard_enforces_branch_scope_for_non_owners(): void
    {
        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard?branch_id=all')
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard?branch_id='.$this->branchTwo->id)
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard?branch_id='.$this->branchOne->id)
            ->assertOk()
            ->assertJsonPath('role', 'manager');
    }

    public function test_owner_and_manager_dashboards_compute_receivables_orders_and_production_counts(): void
    {
        $unpaidBranchOne = $this->invoice($this->branchOne, $this->designer, 1, 25000);
        $unpaidBranchTwo = $this->invoice($this->branchTwo, $this->otherDesigner, 0, 15000);

        $this->order($unpaidBranchOne, 0);
        $this->order($unpaidBranchOne, 1);
        $this->order($unpaidBranchOne, 2);
        $this->order($unpaidBranchOne, 3);
        $this->order($unpaidBranchTwo, 0);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/dashboard?branch_id=all')
            ->assertOk()
            ->assertJsonPath('finance.receivables', '40000.00')
            ->assertJsonPath('order_count', 5)
            ->assertJsonPath('production', [
                'queued' => 2,
                'processing' => 1,
                'completed' => 1,
                'failed' => 1,
            ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/dashboard?branch_id='.$this->branchOne->id)
            ->assertOk()
            ->assertJsonPath('finance.receivables', '25000.00')
            ->assertJsonPath('order_count', 4)
            ->assertJsonPath('production', [
                'queued' => 1,
                'processing' => 1,
                'completed' => 1,
                'failed' => 1,
            ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('period', 'daily')
            ->assertJsonPath('finance.gross_revenue', '100000.00')
            ->assertJsonPath('finance.expenses', '10000.00')
            ->assertJsonPath('finance.receivables', '25000.00')
            ->assertJsonPath('order_count', 4)
            ->assertJsonPath('production', [
                'queued' => 1,
                'processing' => 1,
                'completed' => 1,
                'failed' => 1,
            ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard?period=monthly')
            ->assertOk()
            ->assertJsonPath('period', 'monthly')
            ->assertJsonPath('order_count', 4);
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
        $today = now()->toDateString();
        $query = "?start_date={$today}&end_date={$today}";

        // Guest assertions must run before actingAs() mutates the in-memory auth guard.
        foreach (['gross-revenue', 'base-cost', 'net-profit', 'expenses'] as $report) {
            $this->getJson('/api/v1/reports/'.$report.$query)
                ->assertUnauthorized();
        }

        $this->actingAs($this->manager)
            ->getJson('/api/v1/reports/sparkline')
            ->assertForbidden();

        $branchlessManager = $this->user('manager', null);

        foreach (['gross-revenue', 'base-cost', 'net-profit', 'expenses'] as $report) {
            foreach ([$this->cashier, $this->designer, $this->production, $branchlessManager] as $actor) {
                $this->actingAs($actor)
                    ->getJson('/api/v1/reports/'.$report.$query)
                    ->assertForbidden();
            }
        }
    }

    public function test_manager_can_read_own_branch_financial_reports_and_is_denied_cross_branch(): void
    {
        $today = now()->toDateString();
        $range = "start_date={$today}&end_date={$today}";

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/gross-revenue?{$range}")
            ->assertOk()
            ->assertJsonPath('grand_total', '100000.00');

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/base-cost?{$range}")
            ->assertOk()
            ->assertJsonPath('total_hpp', '80000.00');

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/net-profit?{$range}")
            ->assertOk()
            ->assertJsonPath('net_profit', '10000.00');

        $this->actingAs($this->manager)
            ->getJson("/api/v1/reports/expenses?{$range}")
            ->assertOk()
            ->assertJsonPath('expenses_total', '10000.00');

        foreach (['gross-revenue', 'base-cost', 'net-profit', 'expenses'] as $report) {
            $this->actingAs($this->manager)
                ->getJson("/api/v1/reports/{$report}?branch_id={$this->branchOne->id}&{$range}")
                ->assertOk();

            $this->actingAs($this->manager)
                ->getJson("/api/v1/reports/{$report}?branch_id=all&{$range}")
                ->assertForbidden();

            $this->actingAs($this->manager)
                ->getJson("/api/v1/reports/{$report}?branch_id={$this->branchTwo->id}&{$range}")
                ->assertForbidden();
        }
    }

    public function test_financial_reports_support_period_presets_and_validate_date_ranges(): void
    {
        foreach (['daily', 'weekly', 'monthly'] as $preset) {
            $this->actingAs($this->owner)
                ->getJson("/api/v1/reports/gross-revenue?period={$preset}")
                ->assertOk()
                ->assertJsonPath('grand_total', '100000.00');

            $this->actingAs($this->manager)
                ->getJson("/api/v1/reports/expenses?period={$preset}")
                ->assertOk()
                ->assertJsonPath('expenses_total', '10000.00');
        }

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/gross-revenue?period=yearly')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/reports/gross-revenue?period=custom')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/net-profit?start_date=2026-11-10&end_date=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
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
            ->assertJsonPath('wasted_cost_total', '20000.00')
            ->assertJsonPath('wasted_materials.0.material.unit', 'm2');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/expenses?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('expenses_total', '10000.00')
            ->assertJsonPath('by_category.Supplies', '10000.00');
    }

    public function test_owner_reports_tolerate_waste_from_a_soft_deleted_material(): void
    {
        $this->material->delete();

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/base-cost?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('total_hpp', '60000.00')
            ->assertJsonPath('wasted_cost_total', '0.00')
            ->assertJsonPath('wasted_materials.0.material', null);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/net-profit?branch_id='.$this->branchOne->id.'&start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('total_hpp', '60000.00')
            ->assertJsonPath('net_profit', '30000.00')
            ->assertJsonPath('wasted_materials.0.material', null);
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

    public function test_sparkline_labels_are_in_indonesian(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/sparkline?period=monthly')
            ->assertOk()
            ->assertJsonPath('labels.0', '3 mgg lalu')
            ->assertJsonPath('labels.1', '2 mgg lalu')
            ->assertJsonPath('labels.2', '1 mgg lalu')
            ->assertJsonPath('labels.3', 'Minggu Ini');

        $labels = $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/sparkline?period=weekly')
            ->assertOk()
            ->json('labels');

        $this->assertContains($labels[6], ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']);
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

    public function test_manager_staff_count_excludes_inactive_users(): void
    {
        User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branchOne->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('counts.staff', 3);
    }

    public function test_leaderboard_keeps_historical_inactive_designers(): void
    {
        $this->designer->update(['is_active' => false]);

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/reports/designer-leaderboard')
            ->assertOk();

        $this->assertContains(
            $this->designer->id,
            collect($response->json('leaders'))->pluck('id')->all()
        );
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

    private function invoice(
        Branch $branch,
        User $designer,
        int $status = 2,
        int $accountsReceivable = 0,
    ): Invoice {
        return Invoice::create([
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'designer_id' => $designer->id,
            'status' => $status,
            'discount' => 0,
            'dp_amount' => 100000 - $accountsReceivable,
            'accounts_receivable' => $accountsReceivable,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ]);
    }

    private function order(Invoice $invoice, int $status): void
    {
        Order::create([
            'invoice_id' => $invoice->id,
            'file_name' => 'job-'.$status.'.pdf',
            'notes' => 'Order fixture',
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
