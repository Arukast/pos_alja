<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegisterDownPayment
{
    /**
     * @param  array{amount: numeric, payment_method: string}  $data
     */
    public function execute(Invoice $invoice, array $data, User $cashier): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $data, $cashier): Payment {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 2) {
                throw ValidationException::withMessages([
                    'amount' => 'This invoice is already fully paid.',
                ]);
            }

            $receivable = round((float) $locked->accounts_receivable, 2);
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount must be greater than zero.',
                ]);
            }

            if ($amount > $receivable + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the remaining receivable.',
                ]);
            }

            $payment = Payment::create([
                'branch_id' => $locked->branch_id,
                'invoice_id' => $locked->id,
                'user_id' => $cashier->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'type' => 'down_payment',
                'cashier_name' => $cashier->name,
            ]);

            $locked->recalculatePayments();

            return $payment;
        });

        SafeBroadcast::orderUpdated($invoice->branch_id, $cashier);

        return $payment;
    }
}
