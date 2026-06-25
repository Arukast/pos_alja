<?php

namespace App\Livewire\Dashboards;

use App\Models\Expense;
use App\Models\Invoice;
use Livewire\Component;

class Cashier extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Expense logging properties
    public string $amount = '';
    public string $description = '';
    public string $type = 'laba'; // Default to operations expense
    public string $incurredAt = '';

    // Checkout properties
    public ?int $selectedInvoiceId = null;
    public string $paymentType = 'settle'; // 'dp' or 'settle'
    public string $inputAmount = '';
    public int $inputDiscount = 0;
    public string $inputMethod = 'Cash';

    // Alert feedback
    public bool $showSuccessAlert = false;
    public string $successMessage = '';

    public function mount(): void
    {
        $this->incurredAt = now()->toDateString();
    }

    public function logExpense(): void
    {
        $this->validate([
            'amount'      => 'required|integer|min:100',
            'type'        => 'required|string|in:modal,laba',
            'incurredAt'  => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        Expense::create([
            'branch_id'    => auth()->user()->branch_id ?: 1,
            'amount'       => (int) $this->amount,
            'type'         => $this->type,
            'incurred_at'  => $this->incurredAt,
            'description'  => $this->description,
            'cashier_name' => auth()->user()->name,
        ]);

        $this->reset(['amount', 'description']);
        $this->incurredAt = now()->toDateString();
        $this->type = 'laba';

        $this->successMessage = 'Expense logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function selectInvoice(int $id, string $type = 'settle'): void
    {
        $this->selectedInvoiceId = $id;
        $this->paymentType = $type;
        $invoice = Invoice::findOrFail($id);

        if ($type === 'dp') {
            $this->inputAmount = (string) (int) ($invoice->grand_total * 0.5); // suggest 50% DP
        } else {
            $this->inputAmount = (string) $invoice->accounts_receivable;
        }
        $this->inputDiscount = 0;
        $this->inputMethod = 'Cash';
        $this->resetErrorBag();
    }

    public function closeCheckout(): void
    {
        $this->selectedInvoiceId = null;
    }

    public function processCheckout(): void
    {
        $invoice = Invoice::findOrFail($this->selectedInvoiceId);

        if ($this->paymentType === 'dp') {
            $this->validate([
                'inputAmount' => 'required|integer|min:1000|max:' . $invoice->grand_total,
                'inputMethod' => 'required|string|in:Cash,Transfer,QRIS',
            ]);

            $dpAmt = (int) $this->inputAmount;

            $invoice->dp_amount = $dpAmt;
            $invoice->dp_paid = $dpAmt;
            $invoice->payment_dp_method = $this->inputMethod;
            $invoice->cashier_dp_name = auth()->user()->name;
            $invoice->dp_date = now()->toDateString();
            $invoice->accounts_receivable = $invoice->grand_total - $dpAmt;
            $invoice->status = 1; // Down Payment
            $invoice->save();

            $this->successMessage = 'Down Payment registered successfully!';
        } else {
            $maxDiscount = $invoice->accounts_receivable;
            $this->validate([
                'inputDiscount' => 'required|integer|min:0|max:' . $maxDiscount,
                'inputAmount'   => 'required|integer|min:0',
                'inputMethod'   => 'required|string|in:Cash,Transfer,QRIS',
            ]);

            $discount = (int) $this->inputDiscount;
            $amountToPay = $invoice->accounts_receivable - $discount;
            $cashPaid = (int) $this->inputAmount;

            if ($cashPaid < $amountToPay) {
                $this->addError('inputAmount', 'Insufficient payment. Amount must be at least Rp. ' . number_format($amountToPay));
                return;
            }

            // Adjust invoice totals
            $invoice->discount += $discount;
            $invoice->grand_total -= $discount;
            $invoice->total_profit = max(0, $invoice->total_profit - $discount);
            $invoice->settlement_amount = $amountToPay;
            $invoice->payment_amount = $cashPaid;
            $invoice->change_amount = $cashPaid - $amountToPay;
            $invoice->payment_method = $this->inputMethod;
            $invoice->cashier_name = auth()->user()->name;
            $invoice->payment_date = now()->toDateString();
            $invoice->accounts_receivable = 0;
            $invoice->status = 2; // Paid / Lunas
            $invoice->save();

            $this->successMessage = 'Payment settled and invoice closed!';
        }

        event(new \App\Events\OrderUpdated());

        $this->selectedInvoiceId = null;
        $this->showSuccessAlert = true;
    }

    public function markAsPickedUp(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (auth()->user()->branch_id && $invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $invoice->pickup_status = 1;
        $invoice->pickup_date = now()->toDateString();
        $invoice->save();

        $this->successMessage = 'Order marked as Picked Up!';
        $this->showSuccessAlert = true;

        // Broadcast via WebSocket (Reverb) for cross-role sync
        event(new \App\Events\OrderUpdated());

        // Also directly refresh this component in case Reverb is not running
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: 1;

        // Load recent expenses logged by this branch
        $expenses = Expense::where('branch_id', $branchId)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Calculate today's total expenses for this branch
        $todayExpenseTotal = Expense::where('branch_id', $branchId)
            ->whereDate('incurred_at', now()->toDateString())
            ->sum('amount');

        // Query actual active unpaid/DP invoices for this branch
        $activeInvoices = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->whereIn('status', [0, 1]) // 0: Unpaid, 1: DP
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Calculate checkout metrics for this branch
        $awaitingCount = Invoice::where('branch_id', $branchId)->where('status', 0)->count();
        $dpCollectedToday = Invoice::where('branch_id', $branchId)
            ->whereDate('dp_date', now()->toDateString())  // Use dp_date: the date DP was actually paid
            ->where('status', 1)
            ->sum('dp_amount');
        $settlementsToday = Invoice::where('branch_id', $branchId)
            ->whereDate('payment_date', now()->toDateString())  // Use payment_date: the date settlement occurred
            ->where('status', 2)
            ->sum('settlement_amount');

        // Fetch paid, not yet picked up invoices for Customer Pickup Queue in this branch
        $pickupQueue = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->where('status', 2)       // Paid / Lunas
            ->where('pickup_status', 0) // Not Picked Up
            ->orderBy('payment_date', 'asc')
            ->get();

        return view('livewire.dashboards.cashier', compact(
            'expenses',
            'todayExpenseTotal',
            'activeInvoices',
            'awaitingCount',
            'dpCollectedToday',
            'settlementsToday',
            'pickupQueue'
        ));
    }
}
