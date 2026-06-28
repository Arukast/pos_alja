<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboards\Owner\NetProfitDetail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NetProfitDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Branch $branch1;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch1 = Branch::create([
            'name' => 'Branch One',
        ]);

        $this->branch2 = Branch::create([
            'name' => 'Branch Two',
        ]);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'username' => 'testowner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_net_profit_detail_scopes_by_branch_properly(): void
    {
        $this->actingAs($this->owner);

        // Branch 1 customer
        $cust1 = Customer::create([
            'name' => 'John Retail',
            'type' => 'Retail',
        ]);
        // Branch 1 invoice
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'branch_id' => $this->branch1->id,
            'customer_id' => $cust1->id,
            'created_date' => now()->toDateString(),
            'grand_total' => 100000,
            'total_cost' => 40000,
            'total_profit' => 60000,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 'Walk-in',
        ]);

        // Branch 1 Payment
        Payment::create([
            'invoice_id' => $inv1->id,
            'branch_id' => $this->branch1->id,
            'amount' => 100000,
            'payment_method' => 'Cash',
            'type' => 'settlement',
            'cashier_name' => 'Cashier One',
        ]);

        // Branch 1 Expense
        Expense::create([
            'branch_id' => $this->branch1->id,
            'amount' => 10000,
            'incurred_at' => now()->toDateString(),
            'type' => 'laba',
            'category' => 'Operational',
            'description' => 'Office Supplies',
            'cashier_name' => 'Cashier One',
        ]);

        // Branch 2 customer
        $cust2 = Customer::create([
            'name' => 'Jane Retail',
            'type' => 'Retail',
        ]);
        // Branch 2 invoice
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0002',
            'branch_id' => $this->branch2->id,
            'customer_id' => $cust2->id,
            'created_date' => now()->toDateString(),
            'grand_total' => 200000,
            'total_cost' => 80000,
            'total_profit' => 120000,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 'Walk-in',
        ]);

        // Branch 2 Payment
        Payment::create([
            'invoice_id' => $inv2->id,
            'branch_id' => $this->branch2->id,
            'amount' => 200000,
            'payment_method' => 'Cash',
            'type' => 'settlement',
            'cashier_name' => 'Cashier Two',
        ]);

        // Test Branch 1 Isolation
        Livewire::test(NetProfitDetail::class, [
            'selectedBranchId' => $this->branch1->id,
            'period' => 'Daily',
        ])
            ->assertSet('selectedBranchId', $this->branch1->id)
            ->assertViewHas('net_profit', 50000) // 100000 (Revenue) - 40000 (HPP) - 10000 (Expense)
            ->assertViewHas('gross_revenue', 100000)
            ->assertViewHas('total_hpp', 40000)
            ->assertViewHas('expenses_total', 10000)
            ->assertViewHas('invoices', function ($invoices) use ($inv1) {
                return $invoices->count() === 1 && $invoices->first()->id === $inv1->id;
            });

        // Test Global Overview
        Livewire::test(NetProfitDetail::class, [
            'selectedBranchId' => 'all',
            'period' => 'Daily',
        ])
            ->assertSet('selectedBranchId', 'all')
            ->assertViewHas('net_profit', 170000) // (100k+200k) - (40k+80k) - 10k
            ->assertViewHas('gross_revenue', 300000)
            ->assertViewHas('total_hpp', 120000)
            ->assertViewHas('expenses_total', 10000);
    }

    public function test_net_profit_detail_date_filters_validation(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(NetProfitDetail::class, [
            'selectedBranchId' => 'all',
            'period' => 'Daily',
        ])
            ->set('form.startDate', '2026-06-30')
            ->set('form.endDate', '2026-06-01') // Invalid end date
            ->call('applyCustomRange')
            ->assertHasErrors(['form.endDate' => 'after_or_equal']);
    }
}
