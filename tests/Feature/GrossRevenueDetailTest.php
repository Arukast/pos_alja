<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboards\Owner\GrossRevenueDetail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrossRevenueDetailTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch1;

    protected Branch $branch2;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch1 = Branch::create(['name' => 'Branch One']);
        $this->branch2 = Branch::create(['name' => 'Branch Two']);

        $this->customer = Customer::create([
            'name' => 'John Customer',
            'type' => 'Retail',
        ]);
    }

    public function test_gross_revenue_detail_scopes_by_branch_properly(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null,
        ]);

        $invoice1 = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'branch_id' => $this->branch1->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $invoice2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0002',
            'branch_id' => $this->branch2->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 2,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $payment1 = Payment::create([
            'branch_id' => $this->branch1->id,
            'invoice_id' => $invoice1->id,
            'user_id' => $owner->id,
            'amount' => 50000,
            'payment_method' => 'Cash',
            'type' => 'settlement',
            'cashier_name' => $owner->name,
            'created_at' => now(),
        ]);

        $payment2 = Payment::create([
            'branch_id' => $this->branch2->id,
            'invoice_id' => $invoice2->id,
            'user_id' => $owner->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'type' => 'settlement',
            'cashier_name' => $owner->name,
            'created_at' => now(),
        ]);

        // Branch 1 details only
        Livewire::actingAs($owner)
            ->test(GrossRevenueDetail::class, [
                'selectedBranchId' => $this->branch1->id,
                'period' => 'Daily',
            ])
            ->assertViewHas('grand_total', 50000)
            ->assertViewHas('cash_total', 50000)
            ->assertViewHas('qris_total', 0)
            ->assertViewHas('payments', function ($payments) use ($payment1, $payment2) {
                return $payments->contains($payment1) && ! $payments->contains($payment2);
            });

        // Global "all" details
        Livewire::actingAs($owner)
            ->test(GrossRevenueDetail::class, [
                'selectedBranchId' => 'all',
                'period' => 'Daily',
            ])
            ->assertViewHas('grand_total', 150000)
            ->assertViewHas('cash_total', 50000)
            ->assertViewHas('qris_total', 100000)
            ->assertViewHas('payments', function ($payments) use ($payment1, $payment2) {
                return $payments->contains($payment1) && $payments->contains($payment2);
            });
    }

    public function test_gross_revenue_detail_date_filters_validation(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null,
        ]);

        Livewire::actingAs($owner)
            ->test(GrossRevenueDetail::class, [
                'selectedBranchId' => 'all',
                'period' => 'Daily',
            ])
            ->set('form.startDate', '2026-06-25')
            ->set('form.endDate', '2026-06-20') // Invalid: end date before start date
            ->call('applyCustomRange')
            ->assertHasErrors(['form.endDate']);
    }
}
