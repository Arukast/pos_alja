<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Decimal;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegisterDownPayment
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{amount: numeric, payment_method: string}  $data
     */
    public function execute(Invoice $invoice, array $data, User $cashier): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $data, $cashier): Payment {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 2) {
                throw ValidationException::withMessages([
                    'amount' => 'Invoice ini sudah lunas.',
                ]);
            }

            $statusBefore = $locked->status;
            $receivableBefore = Decimal::money($locked->accounts_receivable);

            $receivable = round((float) $locked->accounts_receivable, 2);
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah pembayaran harus lebih besar dari nol.',
                ]);
            }

            if ($amount > $receivable + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran tidak boleh melebihi sisa tagihan.',
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

            $this->audit->record(
                $cashier,
                'PAYMENT',
                'Menerima down payment invoice '.$locked->invoice_number,
                $payment,
                [
                    'invoice_id' => $locked->id,
                    'amount' => Decimal::money($payment->amount),
                    'method' => $payment->payment_method,
                    'status_before' => $statusBefore,
                    'status_after' => $locked->status,
                    'receivable_before' => $receivableBefore,
                    'receivable_after' => Decimal::money($locked->accounts_receivable),
                    'dp_after' => Decimal::money($locked->dp_amount),
                ],
                $locked->branch_id,
            );

            return $payment;
        });

        SafeBroadcast::orderUpdated($invoice->branch_id, $cashier);

        return $payment;
    }
}
