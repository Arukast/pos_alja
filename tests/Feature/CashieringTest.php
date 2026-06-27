<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Livewire\Dashboards\Cashier;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class CashieringTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed branch
        $this->branch = Branch::create([
            'name' => 'Main Branch',
        ]);
    }

    /**
     * Test Cashier can register Down Payment on invoice.
     */
    public function test_cashier_can_register_down_payment(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $customer = Customer::create([
            'name' => 'John Customer',
            'type' => 'Retail',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0001',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 100000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Cashier::class)
            ->set('selectedInvoiceId', $invoice->id)
            ->set('paymentType', 'dp')
            ->set('inputAmount', 40000)
            ->set('inputMethod', 'Transfer')
            ->call('processCheckout')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 1, // Down Payment
            'dp_amount' => 40000,
            'accounts_receivable' => 60000,
        ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 40000,
            'type' => 'down_payment',
            'payment_method' => 'Transfer',
            'cashier_name' => $user->name,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * Test Cashier can settle outstanding invoice payment.
     */
    public function test_cashier_can_settle_invoice_payment(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $customer = Customer::create([
            'name' => 'Jane Customer',
            'type' => 'Retail',
        ]);

        // Settle from Unpaid status
        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0002',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 150000,
            'grand_total' => 150000,
            'total_cost' => 90000,
            'total_profit' => 60000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Cashier::class)
            ->set('selectedInvoiceId', $invoice->id)
            ->set('paymentType', 'settle')
            ->set('inputDiscount', 10000)
            ->set('inputAmount', 140000)
            ->set('inputMethod', 'QRIS')
            ->call('processCheckout')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 2, // Paid / Lunas
            'discount' => 10000,
            'grand_total' => 140000,
            'accounts_receivable' => 0,
        ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 140000,
            'type' => 'settlement',
            'payment_method' => 'QRIS',
            'cashier_name' => $user->name,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * Test validation guards for cashier checkout.
     */
    public function test_checkout_validation_guards_insufficient_amount(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'type' => 'Retail',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0003',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'status' => 0,
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 100000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 0,
            'source' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Cashier::class)
            ->set('selectedInvoiceId', $invoice->id)
            ->set('paymentType', 'settle')
            ->set('inputDiscount', 5000) // net to pay is 95000
            ->set('inputAmount', 90000)  // insufficient cash
            ->set('inputMethod', 'Cash')
            ->call('processCheckout')
            ->assertHasErrors(['inputAmount']);
    }

    /**
     * Test receipt print page rendering.
     */
    public function test_receipt_print_page_is_renderable(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $customer = Customer::create([
            'name' => 'Alice Customer',
            'type' => 'Retail',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MB-20260627-0004',
            'branch_id' => $this->branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customer->id,
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

        $response = $this->actingAs($user)->get("/orders/{$invoice->id}/receipt");
        $response->assertStatus(200);
        $response->assertSee('Alice Customer');
        $response->assertSee(config('brand.fullname', 'ALJA DIGIPRINT'));
    }
}
