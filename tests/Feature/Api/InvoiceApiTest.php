<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private User $designer;

    private User $production;

    private User $otherCashier;

    private Customer $customer;

    private Invoice $invoiceOne;

    private Invoice $invoiceTwo;

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
        $this->otherCashier = $this->user('cashier', $this->branchTwo);

        $this->customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $this->invoiceOne = $this->invoice($this->branchOne, [
            'invoice_number' => 'INV-B1-001',
            'status' => 1,
        ]);
        $this->invoiceTwo = $this->invoice($this->branchTwo, [
            'invoice_number' => 'INV-B2-001',
            'status' => 1,
        ]);

        $this->order($this->invoiceOne);
        $this->order($this->invoiceTwo);

        $this->payment($this->invoiceOne, $this->branchOne, $this->cashier);
        $this->payment($this->invoiceTwo, $this->branchTwo, $this->otherCashier);
    }

    public function test_customer_search_matches_name_and_phone_for_cashier_and_designer(): void
    {
        foreach ([$this->cashier, $this->designer] as $user) {
            $this->actingAs($user)
                ->getJson('/api/v1/customers?search=Jane')
                ->assertOk()
                ->assertJsonFragment(['id' => $this->customer->id]);

            $this->actingAs($user)
                ->getJson('/api/v1/customers?search=081234')
                ->assertOk()
                ->assertJsonFragment(['id' => $this->customer->id]);
        }
    }

    public function test_production_cannot_search_customers(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/customers?search=Jane')
            ->assertForbidden();
    }

    public function test_invoice_list_is_branch_scoped(): void
    {
        $cashierResponse = $this->actingAs($this->cashier)
            ->getJson('/api/v1/invoices')
            ->assertOk();

        $cashierIds = collect($cashierResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->invoiceOne->id, $cashierIds);
        $this->assertNotContains($this->invoiceTwo->id, $cashierIds);

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices')
            ->assertOk();

        $ownerIds = collect($ownerResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->invoiceOne->id, $ownerIds);
        $this->assertContains($this->invoiceTwo->id, $ownerIds);
    }

    public function test_invoice_list_is_available_to_production_without_money(): void
    {
        $this->actingAs($this->production)
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonMissingPath('data.0.grand_total')
            ->assertJsonMissingPath('data.0.total_cost')
            ->assertJsonMissingPath('data.0.total_profit')
            ->assertJsonMissingPath('data.0.accounts_receivable')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);
    }

    public function test_invoice_detail_includes_customer_orders_and_payments(): void
    {
        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceOne->id)
            ->assertJsonPath('invoice.customer.id', $this->customer->id)
            ->assertJsonPath('invoice.orders.0.file_name', 'banner.pdf')
            ->assertJsonPath('invoice.payments.0.amount', '50000.00')
            ->assertJsonPath('invoice.grand_total', '100000.00');
    }

    public function test_production_invoice_detail_redacts_every_money_field(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}")
            ->assertOk()
            ->assertJsonPath('invoice.orders.0.file_name', 'banner.pdf')
            ->assertJsonMissingPath('invoice.grand_total')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.total_profit')
            ->assertJsonMissingPath('invoice.discount')
            ->assertJsonMissingPath('invoice.accounts_receivable')
            ->assertJsonMissingPath('invoice.payments')
            ->assertJsonMissingPath('invoice.orders.0.unit_price')
            ->assertJsonMissingPath('invoice.orders.0.subtotal')
            ->assertJsonMissingPath('invoice.orders.0.cost')
            ->assertJsonMissingPath('invoice.orders.0.profit');
    }

    public function test_production_cannot_open_receipt(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/receipt")
            ->assertForbidden();
    }

    public function test_production_ticket_is_allowed_and_has_no_money(): void
    {
        $this->actingAs($this->production)
            ->getJson("/api/v1/invoices/{$this->invoiceOne->id}/ticket")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceOne->id)
            ->assertJsonMissingPath('invoice.grand_total')
            ->assertJsonMissingPath('invoice.total_cost')
            ->assertJsonMissingPath('invoice.orders.0.unit_price');
    }

    public function test_cross_branch_ticket_and_receipt_are_forbidden(): void
    {
        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/ticket")
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}/receipt")
            ->assertForbidden();
    }

    public function test_owner_can_read_any_branch_invoice(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/v1/invoices/{$this->invoiceTwo->id}")
            ->assertOk()
            ->assertJsonPath('invoice.id', $this->invoiceTwo->id);
    }

    public function test_invoice_search_matches_number_customer_or_phone(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?search=INV-B2-001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceTwo->id);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?search=Jane')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_invoice_status_filter(): void
    {
        $this->invoiceOne->update(['status' => 2]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/invoices?status=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->invoiceOne->id);
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

    private function invoice(Branch $branch, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'branch_id' => $branch->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'designer_id' => $this->designer->id,
            'status' => 1,
            'discount' => 0,
            'dp_amount' => 50000,
            'accounts_receivable' => 50000,
            'grand_total' => 100000,
            'total_cost' => 60000,
            'total_profit' => 40000,
            'deadline' => now()->addDay()->toDateString(),
            'pickup_status' => 0,
            'source' => 0,
        ], $overrides));
    }

    private function order(Invoice $invoice): void
    {
        Order::create([
            'invoice_id' => $invoice->id,
            'file_name' => 'banner.pdf',
            'notes' => 'Test item',
            'area' => 2,
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 100000,
            'cost' => 60000,
            'profit' => 40000,
            'ordered_at' => now()->toDateString(),
            'status' => 0,
            'pickup_status' => 0,
            'is_manual_price' => false,
        ]);
    }

    private function payment(Invoice $invoice, Branch $branch, User $cashier): void
    {
        Payment::create([
            'branch_id' => $branch->id,
            'invoice_id' => $invoice->id,
            'user_id' => $cashier->id,
            'amount' => 50000,
            'payment_method' => 'Cash',
            'type' => 'down_payment',
            'cashier_name' => $cashier->name,
        ]);
    }
}
