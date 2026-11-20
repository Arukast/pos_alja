<?php

declare(strict_types=1);

namespace App\Modules\Production\Actions;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompleteProduction
{
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
                    'status' => 'Only processing orders can be completed.',
                ]);
            }

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

            return $locked;
        });

        event(new OrderUpdated($updated->invoice->branch_id));

        return $updated->load(['invoice.customer', 'invoice.designer', 'operator', 'pricing']);
    }
}
