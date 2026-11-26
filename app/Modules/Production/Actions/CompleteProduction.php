<?php

declare(strict_types=1);

namespace App\Modules\Production\Actions;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompleteProduction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(Order $order, User $operator): Order
    {
        $updated = DB::transaction(function () use ($order, $operator): Order {
            $locked = Order::query()
                ->with(['invoice', 'pricing.product'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan yang sedang diproses yang dapat diselesaikan.',
                ]);
            }

            $statusBefore = $locked->status;
            $operatorBefore = $locked->operator_id;

            $locked->status = 2;
            $locked->operator_id = $operator->id;
            $locked->save();

            OrderStatusLog::create([
                'order_id' => $locked->id,
                'status' => 2,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => 'Production completed',
            ]);

            $this->audit->record(
                $operator,
                'ORDER_COMPLETE',
                'Menyelesaikan produksi pesanan '.$locked->file_name,
                $locked,
                [
                    'status_before' => $statusBefore,
                    'status_after' => $locked->status,
                    'operator_before' => $operatorBefore,
                    'operator_after' => $locked->operator_id,
                ],
            );

            return $locked;
        });

        SafeBroadcast::orderUpdated($updated->invoice->branch_id, $operator);

        return $updated->load(['invoice.customer', 'invoice.designer', 'operator', 'pricing']);
    }
}
