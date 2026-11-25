<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Validation\ValidationException;

final class LogWaste
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $operator): WastedMaterial
    {
        $branchId = $operator->branch_id;

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Pengguna produksi harus ditugaskan ke cabang.',
            ]);
        }

        $hasStock = MaterialBranchStock::query()
            ->where('material_id', $data['material_id'])
            ->where('branch_id', $branchId)
            ->exists();

        if (! $hasStock) {
            throw ValidationException::withMessages([
                'material_id' => 'Bahan ini tidak tersedia di cabang Anda.',
            ]);
        }

        if (isset($data['designer_id'])) {
            $designerInBranch = User::query()
                ->whereKey($data['designer_id'])
                ->where('role', 'designer')
                ->where('branch_id', $branchId)
                ->exists();

            if (! $designerInBranch) {
                throw ValidationException::withMessages([
                    'designer_id' => 'Desainer ini milik cabang lain.',
                ]);
            }
        }

        $order = Order::query()
            ->whereKey($data['order_id'])
            ->whereHas(
                'invoice',
                fn ($query) => $query->where('branch_id', $branchId)
            )
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order_id' => 'Pesanan ini milik cabang lain.',
            ]);
        }

        if (! in_array($order->status, [0, 1, 3], true)) {
            throw ValidationException::withMessages([
                'order_id' => 'Pesanan yang sudah selesai tidak dapat dicatat sisa bahannya.',
            ]);
        }

        $waste = $this->inventory->logWaste([
            'branch_id' => $branchId,
            'material_id' => $data['material_id'],
            'designer_id' => $data['designer_id'] ?? null,
            'operator_id' => $operator->id,
            'order_id' => $order->id,
            'quantity' => $data['quantity'],
            'dimensions' => $data['dimensions'] ?? null,
            'notes' => $data['notes'] ?? 'Print failure',
            'actor' => $operator,
        ]);

        SafeBroadcast::orderUpdated($branchId, $operator);

        return $waste->load(['material', 'designer', 'operator', 'branch']);
    }
}
