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

final class SettleInvoice
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{discount?: numeric|null, payment_method: string, cash_received?: numeric|null}  $data
     */
    public function execute(Invoice $invoice, array $data, User $cashier): ?Payment
    {
        $payment = DB::transaction(function () use ($invoice, $data, $cashier): ?Payment {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 2) {
                throw ValidationException::withMessages([
                    'amount' => 'Invoice ini sudah lunas.',
                ]);
            }

            $statusBefore = $locked->status;
            $grandTotalBefore = Decimal::money($locked->grand_total);
            $receivableBefore = Decimal::money($locked->accounts_receivable);

            $receivable = round((float) $locked->accounts_receivable, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon tidak boleh negatif.',
                ]);
            }

            if ($discount > $receivable + 0.001) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon tidak boleh melebihi sisa tagihan.',
                ]);
            }

            $amount = round(max(0, $receivable - $discount), 2);

            if (isset($data['cash_received']) && (float) $data['cash_received'] < $amount) {
                throw ValidationException::withMessages([
                    'cash_received' => 'Uang diterima kurang dari jumlah pelunasan.',
                ]);
            }

            if ($discount > 0) {
                $locked->discount = round((float) $locked->discount + $discount, 2);
                $locked->grand_total = round(max(0, (float) $locked->grand_total - $discount), 2);
                $locked->total_profit = round(max(0, (float) $locked->total_profit - $discount), 2);
                $locked->save();
            }

            $payment = null;

            if ($amount > 0) {
                $payment = Payment::create([
                    'branch_id' => $locked->branch_id,
                    'invoice_id' => $locked->id,
                    'user_id' => $cashier->id,
                    'amount' => $amount,
                    'payment_method' => $data['payment_method'],
                    'type' => 'settlement',
                    'cashier_name' => $cashier->name,
                ]);
            }

            $locked->recalculatePayments();

            $this->audit->record(
                $cashier,
                'PAYMENT',
                'Melunasi invoice '.$locked->invoice_number,
                $payment ?? $locked,
                [
                    'invoice_id' => $locked->id,
                    'amount' => Decimal::money($amount),
                    'method' => $data['payment_method'],
                    'status_before' => $statusBefore,
                    'status_after' => $locked->status,
                    'discount' => Decimal::money($discount),
                    'grand_total_before' => $grandTotalBefore,
                    'grand_total_after' => Decimal::money($locked->grand_total),
                    'receivable_before' => $receivableBefore,
                    'receivable_after' => Decimal::money($locked->accounts_receivable),
                ],
                $locked->branch_id,
            );

            return $payment;
        });

        SafeBroadcast::orderUpdated($invoice->branch_id, $cashier);

        return $payment;
    }
}
