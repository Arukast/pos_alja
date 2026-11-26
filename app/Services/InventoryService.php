<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InventoryService
{
    private const MAX_STOCK = 99999999.99;

    private const MOVEMENT_TYPES = ['initial', 'purchase', 'usage', 'usage_reversal', 'waste', 'adjustment', 'restock'];

    /**
     * Create a global material catalog entry.
     */
    public function createGlobalMaterial(array $data): Material
    {
        $userId = auth()->id();
        $branchId = auth()->user()->branch_id ?? Branch::value('id');

        try {
            return DB::transaction(function () use ($data) {
                $materialId = (string) Str::uuid();

                return Material::create([
                    'id' => $materialId,
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'type' => $data['type'] ?? null,
                    'dimensions' => $data['dimensions'] ?? null,
                    'unit' => $data['unit'] ?? 'pcs',
                    'cost' => (float) ($data['cost'] ?? 0.0),
                    'price' => (float) ($data['price'] ?? 0.0),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Global material creation failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Initialize branch stock junction footprint.
     */
    public function initializeBranchStock(string $materialId, string $branchId, float $initialStock, float $minStock): void
    {
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($materialId, $branchId, $initialStock, $minStock) {
                $stock = MaterialBranchStock::firstOrCreate(
                    ['material_id' => $materialId, 'branch_id' => $branchId],
                    ['current_stock' => 0, 'min_stock' => $minStock]
                );

                if ($stock->wasRecentlyCreated && $initialStock > 0) {
                    $this->applyMovement(
                        $materialId,
                        $branchId,
                        'initial',
                        $initialStock,
                        notes: 'Initial stock registered on material creation.',
                    );
                }
            });
        } catch (\Throwable $e) {
            Log::error('Branch stock initialization failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'material_id' => $materialId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply one stock movement and write its ledger row.
     *
     * Callers must hold a transaction; this method never opens its own.
     *
     * @return array{old_stock: float, new_stock: float, ledger_id: string}
     */
    public function applyMovement(
        string $materialId,
        string $branchId,
        string $type,
        float $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?User $actor = null,
        bool $absolute = false,
    ): array {
        if (! in_array($type, self::MOVEMENT_TYPES, true)) {
            throw new InvalidArgumentException("Unknown stock movement type [{$type}].");
        }

        $this->ensureStockRow($materialId, $branchId);

        $stock = MaterialBranchStock::where('material_id', $materialId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->firstOrFail();

        $oldStock = (float) $stock->current_stock;
        $addedStock = 0.0;
        $usedStock = 0.0;
        $wastedStock = 0.0;

        if ($type === 'usage' || $type === 'waste') {
            $newStock = round($oldStock - abs($quantity), 2);

            if ($newStock < -self::MAX_STOCK) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak boleh kurang dari -99999999.99.',
                ]);
            }

            if ($type === 'usage') {
                $usedStock = abs($quantity);
            } else {
                $wastedStock = abs($quantity);
            }
        } elseif ($type === 'adjustment') {
            $newStock = round($absolute ? $quantity : $oldStock + $quantity, 2);

            if ($newStock < 0 || $newStock > self::MAX_STOCK) {
                throw ValidationException::withMessages([
                    $absolute ? 'new_quantity' : 'delta' => 'Stok hasil penyesuaian harus antara 0 dan 99999999.99.',
                ]);
            }

            $addedStock = round($newStock - $oldStock, 2);
        } else {
            $addedStock = abs($quantity);
            $newStock = round($oldStock + $addedStock, 2);

            if ($newStock > self::MAX_STOCK) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok melebihi batas maksimum 99999999.99.',
                ]);
            }
        }

        $stock->current_stock = $newStock;
        $stock->save();

        $ledger = StockLedger::create([
            'branch_id' => $branchId,
            'material_id' => $materialId,
            'type' => $type,
            'reason' => $reason,
            'user_id' => $actor->id ?? auth()->id(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'logged_at' => now()->toDateTimeString(),
            'initial_stock' => $oldStock,
            'added_stock' => $addedStock,
            'used_stock' => $usedStock,
            'wasted_stock' => $wastedStock,
            'remaining_stock' => $newStock,
            'notes' => $notes,
        ]);

        return ['old_stock' => $oldStock, 'new_stock' => $newStock, 'ledger_id' => (string) $ledger->id];
    }

    /**
     * Soft-deletes a material from the system catalog and cascades cleanups.
     */
    public function deleteGlobalMaterial(string $materialId): void
    {
        $userId = auth()->id();
        $branchId = auth()->user()->branch_id ?? Branch::value('id');

        try {
            DB::transaction(function () use ($materialId) {
                $material = Material::findOrFail($materialId);
                $material->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Global material deletion failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'material_id' => $materialId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add new stock to material and log in stock ledger.
     */
    public function addStock(Material $material, float $quantity, ?string $notes = null, ?string $branchId = null): Material
    {
        $userId = auth()->id();
        $branchId = $branchId ?: (auth()->user()->branch_id ?? Branch::value('id'));

        try {
            return DB::transaction(function () use ($material, $quantity, $notes, $branchId) {
                $this->applyMovement(
                    $material->id,
                    $branchId,
                    'restock',
                    $quantity,
                    notes: $notes ?: "Manual stock addition: +{$quantity}",
                );

                return $material;
            });
        } catch (\Throwable $e) {
            Log::error('Adding stock failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'material_id' => $material->id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Log print waste/failure, decrement current stock cache, and write to stock ledger.
     */
    public function logWaste(array $params): WastedMaterial
    {
        $userId = auth()->id();
        $branchId = $params['branch_id'] ?? (auth()->user()->branch_id ?? Branch::value('id'));

        try {
            return DB::transaction(function () use ($params, $branchId) {
                $materialId = $params['material_id'];
                $quantity = (float) $params['quantity'];
                $dimensions = $params['dimensions'] ?? null;
                $designerId = $params['designer_id'] ?? null;
                $operatorId = $params['operator_id'] ?? null;
                $orderId = $params['order_id'] ?? null;
                $notes = $params['notes'] ?? 'Print failure';
                $actor = ($params['actor'] ?? null) instanceof User ? $params['actor'] : null;

                $waste = WastedMaterial::create([
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'logged_at' => now()->toDateTimeString(),
                    'material_id' => $materialId,
                    'order_id' => $orderId,
                    'designer_id' => $designerId,
                    'operator_id' => $operatorId,
                    'quantity' => $quantity,
                    'dimensions' => $dimensions,
                    'notes' => $notes,
                ]);

                $this->applyMovement(
                    $materialId,
                    $branchId,
                    'waste',
                    $quantity,
                    referenceType: $orderId !== null ? 'order' : null,
                    referenceId: $orderId,
                    notes: "Failed Print logged: -{$quantity} ({$notes})",
                    actor: $actor,
                );

                return $waste;
            });
        } catch (\Throwable $e) {
            Log::error('Logging waste failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Deduct stock for printed layout items and return the created ledger row.
     */
    public function deductStockOnPrint(
        string $materialId,
        float $quantity,
        ?string $branchId = null,
        ?string $orderId = null,
        ?User $operator = null,
    ): StockLedger {
        $userId = auth()->id();
        $branchId = $branchId ?: (auth()->user()->branch_id ?? Branch::value('id'));

        try {
            return DB::transaction(function () use ($materialId, $quantity, $branchId, $orderId, $operator): StockLedger {
                Material::findOrFail($materialId);

                $movement = $this->applyMovement(
                    $materialId,
                    $branchId,
                    'usage',
                    $quantity,
                    referenceType: $orderId !== null ? 'order' : null,
                    referenceId: $orderId,
                    notes: "Printed order item stock deduction: -{$quantity}",
                    actor: $operator,
                );

                return StockLedger::query()->findOrFail($movement['ledger_id']);
            });
        } catch (\Throwable $e) {
            Log::error('Deducting stock on print failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'material_id' => $materialId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create the branch stock row when it is missing; callers then lock it.
     */
    private function ensureStockRow(string $materialId, string $branchId): void
    {
        $now = now();

        DB::table('material_branch_stock')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'material_id' => $materialId,
            'branch_id' => $branchId,
            'current_stock' => 0,
            'min_stock' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
