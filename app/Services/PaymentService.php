<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Register a down payment on an invoice.
     */
    public function registerDownPayment(Invoice $invoice, array $data, User $cashier): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $cashier) {
            $amount = (float) $data['amount'];
            $method = $data['payment_method'];

            $payment = Payment::create([
                'branch_id' => $invoice->branch_id ?: Branch::value('id'),
                'invoice_id' => $invoice->id,
                'user_id' => $cashier->id,
                'amount' => $amount,
                'payment_method' => $method,
                'type' => 'down_payment',
                'cashier_name' => $cashier->name,
            ]);

            $invoice->recalculatePayments();

            return $payment;
        });
    }

    /**
     * Settle the remaining balance of an invoice.
     */
    public function settleInvoice(Invoice $invoice, array $data, User $cashier): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $cashier) {
            $discount = (float) ($data['discount'] ?? 0.0);
            $amountToPay = (float) $data['amount_to_pay'];
            $method = $data['payment_method'];

            if ($discount > 0) {
                $invoice->discount += $discount;
                $invoice->grand_total = max(0.0, $invoice->grand_total - $discount);
                $invoice->total_profit = max(0.0, $invoice->total_profit - $discount);
                $invoice->save();
            }

            $payment = Payment::create([
                'branch_id' => $invoice->branch_id ?: Branch::value('id'),
                'invoice_id' => $invoice->id,
                'user_id' => $cashier->id,
                'amount' => $amountToPay,
                'payment_method' => $method,
                'type' => 'settlement',
                'cashier_name' => $cashier->name,
            ]);

            $invoice->recalculatePayments();

            return $payment;
        });
    }
}
