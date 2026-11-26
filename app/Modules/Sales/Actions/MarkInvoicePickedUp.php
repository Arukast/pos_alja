<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Invoice;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarkInvoicePickedUp
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(Invoice $invoice, User $actor): Invoice
    {
        $updated = DB::transaction(function () use ($invoice, $actor): Invoice {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 2) {
                throw ValidationException::withMessages([
                    'invoice' => 'Hanya invoice yang sudah lunas yang dapat diambil.',
                ]);
            }

            $pickupStatusBefore = $locked->pickup_status;

            $locked->pickup_status = 1;
            $locked->pickup_date = $locked->pickup_date ?: now()->toDateString();
            $locked->save();

            $this->audit->record(
                $actor,
                'PICKUP',
                'Menandai invoice '.$locked->invoice_number.' sudah diambil',
                $locked,
                [
                    'pickup_status_before' => $pickupStatusBefore,
                    'pickup_status_after' => $locked->pickup_status,
                    'pickup_date' => $locked->pickup_date,
                ],
                $locked->branch_id,
            );

            return $locked;
        });

        SafeBroadcast::orderUpdated($invoice->branch_id, $actor);

        return $updated->load(['customer', 'branch', 'designer', 'orders', 'payments']);
    }
}
