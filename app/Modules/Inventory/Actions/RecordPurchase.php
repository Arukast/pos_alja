<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\ActivityLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\BranchScope;
use App\Support\Decimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class RecordPurchase
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{purchase: Purchase, created: bool}
     */
    public function execute(array $data, User $actor): array
    {
        $branchId = BranchScope::writeBranchId($actor, $data['branch_id'] ?? null);
        $key = (string) $data['idempotency_key'];

        $existing = $this->findExisting($branchId, $key);

        if ($existing !== null) {
            return ['purchase' => $existing, 'created' => false];
        }

        try {
            $purchase = DB::transaction(
                fn (): Purchase => $this->record($data, $actor, $branchId, $key)
            );
        } catch (UniqueConstraintViolationException $exception) {
            $existing = $this->findExisting($branchId, $key);

            if ($existing === null) {
                throw $exception;
            }

            return ['purchase' => $existing, 'created' => false];
        }

        return ['purchase' => $purchase, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function record(array $data, User $actor, string $branchId, string $key): Purchase
    {
        /** @var array<int, array{material_id: string, quantity: float, unit_cost: string, subtotal: string}> $lines */
        $lines = [];
        $totalAmount = '0.00';

        foreach ($data['items'] as $item) {
            $quantity = (string) $item['quantity'];
            $unitCost = (string) $item['unit_cost'];
            $subtotal = Decimal::multiply($quantity, $unitCost);

            $lines[] = [
                'material_id' => (string) $item['material_id'],
                'quantity' => (float) $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $subtotal,
            ];

            $totalAmount = Decimal::add($totalAmount, $subtotal);
        }

        $purchase = Purchase::create([
            'branch_id' => $branchId,
            'user_id' => $actor->id,
            'purchased_at' => $data['purchased_at'] ?? now(),
            'supplier' => $data['supplier'] ?? null,
            'total_amount' => $totalAmount,
            'notes' => $data['notes'] ?? null,
            'idempotency_key' => $key,
        ]);

        foreach ($lines as $line) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'material_id' => $line['material_id'],
                'quantity' => $line['quantity'],
                'unit_cost' => $line['unit_cost'],
                'subtotal' => $line['subtotal'],
            ]);
        }

        $movements = array_map(
            fn (array $line): array => ['material_id' => $line['material_id'], 'quantity' => $line['quantity']],
            $lines
        );

        // Deterministic lock order: concurrent purchases lock stock rows by ascending material id.
        usort(
            $movements,
            fn (array $a, array $b): int => $a['material_id'] <=> $b['material_id']
        );

        foreach ($movements as $movement) {
            $this->inventory->applyMovement(
                $movement['material_id'],
                $branchId,
                'purchase',
                $movement['quantity'],
                referenceType: 'purchase',
                referenceId: $purchase->id,
                actor: $actor,
            );
        }

        ActivityLog::create([
            'branch_id' => $branchId,
            'user_id' => $actor->id,
            'username' => $actor->username,
            'role' => $actor->role,
            'action' => 'CREATE',
            'description' => 'Mencatat pembelian bahan',
            'model_type' => Purchase::class,
            'model_id' => $purchase->id,
            'payload' => [
                'supplier' => $data['supplier'] ?? null,
                'total_amount' => $totalAmount,
                'item_count' => count($lines),
            ],
        ]);

        return $purchase->load(['branch', 'user', 'items.material']);
    }

    private function findExisting(string $branchId, string $key): ?Purchase
    {
        return Purchase::query()
            ->with(['branch', 'user', 'items.material'])
            ->where('branch_id', $branchId)
            ->where('idempotency_key', $key)
            ->first();
    }
}
