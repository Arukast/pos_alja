<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Events\OrderUpdated;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarkInvoicePickedUp
{
    public function execute(Invoice $invoice): Invoice
    {
        $updated = DB::transaction(function () use ($invoice): Invoice {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 2) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only fully paid invoices can be picked up.',
                ]);
            }

            $locked->pickup_status = 1;
            $locked->pickup_date = $locked->pickup_date ?: now()->toDateString();
            $locked->save();

            return $locked;
        });

        event(new OrderUpdated($invoice->branch_id));

        return $updated->load(['customer', 'branch', 'designer', 'orders', 'payments']);
    }
}
