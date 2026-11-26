<?php

declare(strict_types=1);

namespace App\Modules\Production\Actions;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FailProduction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(Order $order, User $operator, string $note): Order
    {
        $updated = DB::transaction(function () use ($order, $operator, $note): Order {
            $locked = Order::query()
                ->with('invoice')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan yang sedang diproses yang dapat ditandai gagal.',
                ]);
            }

            $netUsages = DB::table('stock_ledgers')
                ->where('reference_type', 'order')
                ->where('reference_id', $locked->id)
                ->whereIn('type', ['usage', 'usage_reversal'])
                ->selectRaw(
                    'branch_id, material_id, '
                    ."SUM(CASE WHEN type = 'usage' THEN used_stock ELSE 0 END) - "
                    ."SUM(CASE WHEN type = 'usage_reversal' THEN added_stock ELSE 0 END) AS net_usage"
                )
                ->groupBy('branch_id', 'material_id')
                ->orderBy('material_id')
                ->orderBy('branch_id')
                ->get();

            foreach ($netUsages as $usage) {
                $net = round((float) $usage->net_usage, 2);

                if ($net <= 0) {
                    continue;
                }

                $this->inventory->applyMovement(
                    (string) $usage->material_id,
                    (string) $usage->branch_id,
                    'usage_reversal',
                    $net,
                    referenceType: 'order',
                    referenceId: $locked->id,
                    notes: $note,
                    actor: $operator,
                );
            }

            $locked->status = 3;
            $locked->save();

            OrderStatusLog::create([
                'order_id' => $locked->id,
                'status' => 3,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => $note,
            ]);

            return $locked;
        });

        SafeBroadcast::orderUpdated($updated->invoice?->branch_id, $operator);

        return $updated->load(['invoice.customer', 'invoice.designer', 'operator']);
    }
}
