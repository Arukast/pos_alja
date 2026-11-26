<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $cashier;

    private User $otherCashier;

    private User $owner;

    private User $designer;

    private Invoice $invoice;

    private Invoice $otherInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->cashier = $this->user('cashier', $this->branchOne);
        $this->otherCashier = $this->user('cashier', $this->branchTwo);
        $this->owner = $this->user('owner', null);
        $this->designer = $this->user('designer', $this->branchOne);

        $customer = Customer::create([
            'name' => 'Jane Customer',
            'phone' => '08123456789',
            'type' => 'Reguler',
        ]);

        $this->invoice = $this->invoice($this->branchOne, $customer, 'INV-B1-001');
        $this->otherInvoice = $this->invoice($this->branchTwo, $customer, 'INV-B2-001');
    }

    public function test_cashier_can_register_a_down_payment(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 50000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated()
            ->assertJsonPath('payment.amount', '50000.00')
            ->assertJsonPath('invoice.dp_amount', '50000.00')
            ->assertJsonPath('invoice.accounts_receivable', '50000.00')
            ->assertJsonPath('invoice.status', 1);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 50000,
            'type' => 'down_payment',
        ]);
    }

    public function test_multiple_down_payments_accumulate(): void
    {
        $this->actingAs($this->cashier)->postJson(
            "/api/v1/invoices/{$this->invoice->id}/down-payments",
            ['amount' => 30000, 'payment_method' => 'Transfer']
        )->assertCreated();

        $this->actingAs($this->cashier)->postJson(
            "/api/v1/invoices/{$this->invoice->id}/down-payments",
            ['amount' => 20000, 'payment_method' => 'QRIS']
        )->assertCreated()
            ->assertJsonPath('invoice.dp_amount', '50000.00')
            ->assertJsonPath('invoice.accounts_receivable', '50000.00');
    }

    public function test_down_payment_that_rounds_to_zero_is_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 0.001,
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.amount.0', 'Jumlah pembayaran harus lebih besar dari nol.');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_down_payment_cannot_exceed_receivable(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 120000,
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_full_down_payment_marks_invoice_paid(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 100000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.status', 2)
            ->assertJsonPath('invoice.accounts_receivable', '0.00');
    }

    public function test_settlement_computes_amount_from_receivable_and_discount(): void
    {
        $this->actingAs($this->cashier)->postJson(
            "/api/v1/invoices/{$this->invoice->id}/down-payments",
            ['amount' => 50000, 'payment_method' => 'Cash']
        )->assertCreated();

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'discount' => 10000,
                'payment_method' => 'Cash',
                'cash_received' => 50000,
            ])
            ->assertCreated()
            ->assertJsonPath('payment.amount', '40000.00')
            ->assertJsonPath('invoice.discount', '10000.00')
            ->assertJsonPath('invoice.grand_total', '90000.00')
            ->assertJsonPath('invoice.accounts_receivable', '0.00')
            ->assertJsonPath('invoice.status', 2);
    }

    public function test_settlement_rejects_an_over_discount(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'discount' => 120000,
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount');
    }

    public function test_double_settlement_is_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cashier_cannot_pay_another_branch_invoice(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->otherInvoice->id}/down-payments", [
                'amount' => 10000,
                'payment_method' => 'Cash',
            ])
            ->assertForbidden();
    }

    public function test_non_cashier_roles_cannot_post_payments(): void
    {
        foreach ([$this->owner, $this->designer] as $user) {
            $this->actingAs($user)
                ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                    'amount' => 10000,
                    'payment_method' => 'Cash',
                ])
                ->assertForbidden();
        }
    }

    public function test_pickup_requires_a_paid_invoice(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/pickup")
            ->assertStatus(422);

        $this->invoice->update(['status' => 2, 'accounts_receivable' => 0]);

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/pickup")
            ->assertOk()
            ->assertJsonPath('invoice.pickup_status', 1);
    }

    public function test_pickup_is_branch_scoped(): void
    {
        $this->otherInvoice->update(['status' => 2, 'accounts_receivable' => 0]);

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->otherInvoice->id}/pickup")
            ->assertForbidden();
    }

    public function test_down_payment_writes_payment_audit_with_before_after(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 50000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated();

        $paymentId = (string) $response->json('payment.id');

        $log = ActivityLog::query()
            ->where('action', 'PAYMENT')
            ->where('model_type', Payment::class)
            ->where('model_id', $paymentId)
            ->firstOrFail();

        $this->assertSame($this->cashier->id, $log->user_id);
        $this->assertSame($this->cashier->username, $log->username);
        $this->assertSame('cashier', $log->role);
        $this->assertSame($this->branchOne->id, $log->branch_id);

        $this->assertSame($this->invoice->id, $log->payload['invoice_id']);
        $this->assertSame('50000.00', $log->payload['amount']);
        $this->assertSame('Cash', $log->payload['method']);
        $this->assertSame(0, $log->payload['status_before']);
        $this->assertSame(1, $log->payload['status_after']);
        $this->assertSame('100000.00', $log->payload['receivable_before']);
        $this->assertSame('50000.00', $log->payload['receivable_after']);
        $this->assertSame('50000.00', $log->payload['dp_after']);
    }

    public function test_settlement_writes_payment_audit_with_discount_and_money_strings(): void
    {
        $this->actingAs($this->cashier)->postJson(
            "/api/v1/invoices/{$this->invoice->id}/down-payments",
            ['amount' => 50000, 'payment_method' => 'Cash']
        )->assertCreated();

        $response = $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'discount' => 10000,
                'payment_method' => 'Cash',
                'cash_received' => 50000,
            ])
            ->assertCreated();

        $paymentId = (string) $response->json('payment.id');

        $log = ActivityLog::query()
            ->where('action', 'PAYMENT')
            ->where('model_type', Payment::class)
            ->where('model_id', $paymentId)
            ->firstOrFail();

        $this->assertSame($this->cashier->id, $log->user_id);
        $this->assertSame($this->branchOne->id, $log->branch_id);

        $this->assertSame($this->invoice->id, $log->payload['invoice_id']);
        $this->assertSame('40000.00', $log->payload['amount']);
        $this->assertSame('Cash', $log->payload['method']);
        $this->assertSame('10000.00', $log->payload['discount']);
        $this->assertSame(1, $log->payload['status_before']);
        $this->assertSame(2, $log->payload['status_after']);
        $this->assertSame('100000.00', $log->payload['grand_total_before']);
        $this->assertSame('90000.00', $log->payload['grand_total_after']);
        $this->assertSame('50000.00', $log->payload['receivable_before']);
        $this->assertSame('0.00', $log->payload['receivable_after']);
    }

    public function test_zero_amount_settlement_audits_invoice_as_subject(): void
    {
        $this->actingAs($this->cashier)->postJson(
            "/api/v1/invoices/{$this->invoice->id}/down-payments",
            ['amount' => 50000, 'payment_method' => 'Cash']
        )->assertCreated();

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/settlements", [
                'discount' => 50000,
                'payment_method' => 'Cash',
            ])
            ->assertCreated()
            ->assertJsonPath('payment', null);

        $log = ActivityLog::query()
            ->where('action', 'PAYMENT')
            ->where('model_type', Invoice::class)
            ->where('model_id', $this->invoice->id)
            ->firstOrFail();

        $this->assertSame('0.00', $log->payload['amount']);
        $this->assertSame('50000.00', $log->payload['discount']);
        $this->assertSame(1, $log->payload['status_before']);
        $this->assertSame(2, $log->payload['status_after']);
        $this->assertSame('0.00', $log->payload['receivable_after']);
    }

    public function test_pickup_writes_pickup_audit(): void
    {
        $this->invoice->update(['status' => 2, 'accounts_receivable' => 0]);

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/pickup")
            ->assertOk();

        $log = ActivityLog::query()
            ->where('action', 'PICKUP')
            ->where('model_type', Invoice::class)
            ->where('model_id', $this->invoice->id)
            ->firstOrFail();

        $this->assertSame($this->cashier->id, $log->user_id);
        $this->assertSame($this->branchOne->id, $log->branch_id);
        $this->assertSame(0, $log->payload['pickup_status_before']);
        $this->assertSame(1, $log->payload['pickup_status_after']);
        $this->assertSame($this->invoice->fresh()->pickup_date, $log->payload['pickup_date']);
    }

    public function test_failed_payment_does_not_write_audit(): void
    {
        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->invoice->id}/down-payments", [
                'amount' => 120000,
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422);

        $this->actingAs($this->cashier)
            ->postJson("/api/v1/invoices/{$this->otherInvoice->id}/down-payments", [
                'amount' => 10000,
                'payment_method' => 'Cash',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('activity_logs', 0);
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
            'status' => 0,
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
}
