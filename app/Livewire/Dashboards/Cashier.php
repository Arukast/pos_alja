<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Livewire\Forms\ExpenseForm;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Cashier extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Expense logging Form Object
    public ExpenseForm $expenseForm;

    // Properties accessed inside @island views
    public $activeInvoices;

    public $expenses;

    // Checkout properties
    public ?string $selectedInvoiceId = null;

    public string $paymentType = 'settle'; // 'dp' or 'settle'

    public string $inputAmount = '';

    public int $inputDiscount = 0;

    public string $inputMethod = 'Cash';

    // Alert feedback
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $this->expenseForm->incurred_at = now()->toDateString();
    }

    public function logExpense(): void
    {
        $this->expenseForm->validate();

        Expense::create([
            'branch_id' => auth()->user()->branch_id ?: Branch::value('id'),
            'amount' => (int) $this->expenseForm->amount,
            'type' => $this->expenseForm->type,
            'incurred_at' => $this->expenseForm->incurred_at,
            'description' => $this->expenseForm->description,
            'cashier_name' => auth()->user()->name,
        ]);

        $this->expenseForm->reset();
        $this->expenseForm->incurred_at = now()->toDateString();
        $this->expenseForm->type = 'laba';

        $this->successMessage = 'Expense logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function selectInvoice(string $id, string $type = 'settle'): void
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
        $paymentService = new PaymentService;

        if ($this->paymentType === 'dp') {
            $this->validate([
                'inputAmount' => 'required|integer|min:1000|max:'.$invoice->grand_total,
                'inputMethod' => 'required|string|in:Cash,Transfer,QRIS',
            ]);

            $paymentService->registerDownPayment($invoice, [
                'amount' => (int) $this->inputAmount,
                'payment_method' => $this->inputMethod,
            ], auth()->user());

            $this->successMessage = 'Down Payment registered successfully!';
        } else {
            $maxDiscount = $invoice->accounts_receivable;
            $this->validate([
                'inputDiscount' => 'required|integer|min:0|max:'.$maxDiscount,
                'inputAmount' => 'required|integer|min:0',
                'inputMethod' => 'required|string|in:Cash,Transfer,QRIS',
            ]);

            $discount = (int) $this->inputDiscount;
            $amountToPay = $invoice->accounts_receivable - $discount;
            $cashPaid = (int) $this->inputAmount;

            if ($cashPaid < $amountToPay) {
                $this->addError('inputAmount', 'Insufficient payment. Amount must be at least Rp. '.number_format($amountToPay));

                return;
            }

            $paymentService->settleInvoice($invoice, [
                'discount' => $discount,
                'amount_to_pay' => $amountToPay,
                'payment_method' => $this->inputMethod,
            ], auth()->user());

            $this->successMessage = 'Payment settled and invoice closed!';
        }

        event(new OrderUpdated);

        $this->selectedInvoiceId = null;
        $this->showSuccessAlert = true;
    }

    public function markAsPickedUp(string $invoiceId): void
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
        event(new OrderUpdated);

        // Also directly refresh this component in case Reverb is not running
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: Branch::value('id');

        $this->expenses = collect();
        $todayExpenseTotal = 0;
        $this->activeInvoices = collect();
        $awaitingCount = 0;
        $dpCollectedToday = 0;
        $settlementsToday = 0;
        $pickupQueue = collect();

        if ($this->activeTab === 'dashboard' || $this->activeTab === 'payments') {
            // Query actual active unpaid/DP invoices for this branch
            $this->activeInvoices = Invoice::with('customer')
                ->where('branch_id', $branchId)
                ->whereIn('status', [0, 1]) // 0: Unpaid, 1: DP
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        if ($this->activeTab === 'dashboard' || $this->activeTab === 'pickup') {
            // Fetch paid, not yet picked up invoices for Customer Pickup Queue in this branch
            $pickupQueue = Invoice::with('customer')
                ->where('branch_id', $branchId)
                ->where('status', 2)       // Paid / Lunas
                ->where('pickup_status', 0) // Not Picked Up
                ->orderBy('payment_date', 'asc')
                ->get();
        }

        if ($this->activeTab === 'dashboard' || $this->activeTab === 'expenses') {
            // Load recent expenses logged by this branch
            $this->expenses = Expense::where('branch_id', $branchId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Calculate today's total expenses for this branch
            $todayExpenseTotal = Expense::where('branch_id', $branchId)
                ->whereDate('incurred_at', now()->toDateString())
                ->sum('amount');
        }

        if ($this->activeTab === 'dashboard') {
            // Calculate checkout metrics for this branch
            $awaitingCount = Invoice::where('branch_id', $branchId)->where('status', 0)->count();

            $dpCollectedToday = Payment::where('branch_id', $branchId)
                ->where('type', 'down_payment')
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');

            $settlementsToday = Payment::where('branch_id', $branchId)
                ->where('type', 'settlement')
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
        }

        return view('livewire.dashboards.cashier', compact(
            'todayExpenseTotal',
            'awaitingCount',
            'dpCollectedToday',
            'settlementsToday',
            'pickupQueue'
        ));
    }
}
