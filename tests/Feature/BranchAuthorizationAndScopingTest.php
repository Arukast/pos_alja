<?php

namespace Tests\Feature;

use App\Livewire\Dashboards\Cashier;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchAuthorizationAndScopingTest extends TestCase
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
            'name' => 'Jane Customer',
            'type' => 'Retail',
        ]);
    }

    /**
     * Test cross-branch printing restrictions.
     */
    public function test_non_owner_cannot_access_other_branch_ticket_and_receipt(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch1->id,
        ]);

        $invoiceAtBranch2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'branch_id' => $this->branch2->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 50000,
            'dp_paid' => 0,
            'settlement_amount' => 0,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'customer_service' => 'CS 2',
            'deadline' => 'Tomorrow',
            'pickup_status' => 0,
            'source' => 0,
        ]);

        // Accessing other branch - expect 403
        $responseTicket = $this->actingAs($cashier)->get("/orders/{$invoiceAtBranch2->id}/ticket");
        $responseTicket->assertStatus(403);

        $responseReceipt = $this->actingAs($cashier)->get("/orders/{$invoiceAtBranch2->id}/receipt");
        $responseReceipt->assertStatus(403);

        // Accessing own branch - expect 200
        $invoiceAtBranch1 = Invoice::create([
            'invoice_number' => 'INV-TEST-0002',
            'branch_id' => $this->branch1->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 50000,
            'dp_paid' => 0,
            'settlement_amount' => 0,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'customer_service' => 'CS 1',
            'deadline' => 'Tomorrow',
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $responseOwnTicket = $this->actingAs($cashier)->get("/orders/{$invoiceAtBranch1->id}/ticket");
        $responseOwnTicket->assertStatus(200);

        $responseOwnReceipt = $this->actingAs($cashier)->get("/orders/{$invoiceAtBranch1->id}/receipt");
        $responseOwnReceipt->assertStatus(200);
    }

    /**
     * Test Owner can access any branch ticket and receipt.
     */
    public function test_owner_can_access_any_branch_ticket_and_receipt(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'branch_id' => null, // Owner has global access
        ]);

        $invoiceAtBranch2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0003',
            'branch_id' => $this->branch2->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 50000,
            'dp_paid' => 0,
            'settlement_amount' => 0,
            'grand_total' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'customer_service' => 'CS 2',
            'deadline' => 'Tomorrow',
            'pickup_status' => 0,
            'source' => 0,
        ]);

        $responseTicket = $this->actingAs($owner)->get("/orders/{$invoiceAtBranch2->id}/ticket");
        $responseTicket->assertStatus(200);

        $responseReceipt = $this->actingAs($owner)->get("/orders/{$invoiceAtBranch2->id}/receipt");
        $responseReceipt->assertStatus(200);
    }

    /**
     * Test Cashier Dashboard scopes queries correctly.
     */
    public function test_cashier_dashboard_scopes_active_invoices_and_expenses(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch1->id,
        ]);

        // Invoice in branch 1
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-TEST-0004',
            'branch_id' => $this->branch1->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 0,
            'grand_total' => 50000,
            'accounts_receivable' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'deadline' => 'Tomorrow',
        ]);

        // Invoice in branch 2
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-TEST-0005',
            'branch_id' => $this->branch2->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 0,
            'grand_total' => 90000,
            'accounts_receivable' => 90000,
            'total_cost' => 50000,
            'total_profit' => 40000,
            'deadline' => 'Tomorrow',
        ]);

        // Expense in branch 1
        $exp1 = Expense::create([
            'branch_id' => $this->branch1->id,
            'amount' => 10000,
            'type' => 'laba',
            'incurred_at' => now()->toDateString(),
            'description' => 'Branch 1 operational expense',
            'cashier_name' => 'Cashier 1',
        ]);

        // Expense in branch 2
        $exp2 = Expense::create([
            'branch_id' => $this->branch2->id,
            'amount' => 20000,
            'type' => 'laba',
            'incurred_at' => now()->toDateString(),
            'description' => 'Branch 2 operational expense',
            'cashier_name' => 'Cashier 2',
        ]);

        Livewire::actingAs($cashier)
            ->test(Cashier::class)
            ->assertViewHas('activeInvoices', function ($invoices) use ($inv1, $inv2) {
                return $invoices->contains($inv1) && ! $invoices->contains($inv2);
            })
            ->assertViewHas('expenses', function ($expenses) use ($exp1, $exp2) {
                return $expenses->contains($exp1) && ! $expenses->contains($exp2);
            })
            ->assertViewHas('todayExpenseTotal', 10000);
    }

    /**
     * Test Cashier can mark order as picked up.
     */
    public function test_cashier_can_mark_order_as_picked_up(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch1->id,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-0006',
            'branch_id' => $this->branch1->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'status' => 2, // Paid / Lunas
            'discount' => 0,
            'grand_total' => 50000,
            'accounts_receivable' => 0,
            'settlement_amount' => 50000,
            'total_cost' => 30000,
            'total_profit' => 20000,
            'deadline' => 'Tomorrow',
            'pickup_status' => 0,
        ]);

        Livewire::actingAs($cashier)
            ->test(Cashier::class)
            ->assertViewHas('pickupQueue', function ($queue) use ($invoice) {
                return $queue->contains($invoice);
            })
            ->call('markAsPickedUp', $invoice->id)
            ->assertHasNoErrors()
            ->assertViewHas('pickupQueue', function ($queue) use ($invoice) {
                return ! $queue->contains($invoice);
            });

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'pickup_status' => 1,
            'pickup_date' => now()->toDateString(),
        ]);
    }
}
