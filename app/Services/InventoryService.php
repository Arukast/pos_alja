<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\StockLedger;
use App\Models\WastedMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InventoryService
{
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
                MaterialBranchStock::create([
                    'id' => (string) Str::uuid(),
                    'material_id' => $materialId,
                    'branch_id' => $branchId,
                    'current_stock' => $initialStock,
                    'min_stock' => $minStock,
                ]);

                if ($initialStock > 0) {
                    StockLedger::create([
                        'id' => (string) Str::uuid(),
                        'branch_id' => $branchId,
                        'material_id' => $materialId,
                        'logged_at' => now()->toIso8601String(),
                        'initial_stock' => 0.0,
                        'added_stock' => $initialStock,
                        'used_stock' => 0.0,
                        'wasted_stock' => 0.0,
                        'remaining_stock' => $initialStock,
                        'notes' => 'Initial stock registered on material creation.',
                    ]);
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
                $stock = MaterialBranchStock::where('material_id', $material->id)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrCreate([
                        'material_id' => $material->id,
                        'branch_id' => $branchId,
                    ]);

                $oldStock = (float) $stock->current_stock;
                $newStock = $oldStock + $quantity;

                $stock->current_stock = $newStock;
                $stock->save();

                StockLedger::create([
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'material_id' => $material->id,
                    'logged_at' => now()->toIso8601String(),
                    'initial_stock' => $oldStock,
                    'added_stock' => $quantity,
                    'used_stock' => 0.0,
                    'wasted_stock' => 0.0,
                    'remaining_stock' => $newStock,
                    'notes' => $notes ?: "Manual stock addition: +{$quantity}",
                ]);

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
                $machineId = ! empty($params['machine_id']) ? $params['machine_id'] : null;
                $dimensions = $params['dimensions'] ?? null;
                $designerId = $params['designer_id'] ?? null;
                $operatorId = $params['operator_id'] ?? null;
                $orderId = $params['order_id'] ?? null;
                $notes = $params['notes'] ?? 'Print failure';

                $stock = MaterialBranchStock::where('material_id', $materialId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrCreate([
                        'material_id' => $materialId,
                        'branch_id' => $branchId,
                    ]);

                $oldStock = (float) $stock->current_stock;
                $newStock = $oldStock - $quantity;

                $waste = WastedMaterial::create([
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'logged_at' => now()->toIso8601String(),
                    'material_id' => $materialId,
                    'machine_id' => $machineId,
                    'order_id' => $orderId,
                    'designer_id' => $designerId,
                    'operator_id' => $operatorId,
                    'quantity' => $quantity,
                    'dimensions' => $dimensions,
                    'notes' => $notes,
                ]);

                $stock->current_stock = $newStock;
                $stock->save();

                StockLedger::create([
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'material_id' => $materialId,
                    'logged_at' => now()->toIso8601String(),
                    'initial_stock' => $oldStock,
                    'added_stock' => 0.0,
                    'used_stock' => 0.0,
                    'wasted_stock' => $quantity,
                    'remaining_stock' => $newStock,
                    'notes' => "Failed Print logged: -{$quantity} ({$notes})",
                ]);

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
     * Deduct stock for printed layout items.
     */
    public function deductStockOnPrint(string $materialId, float $quantity, ?string $branchId = null): Material
    {
        $userId = auth()->id();
        $branchId = $branchId ?: (auth()->user()->branch_id ?? Branch::value('id'));

        try {
            return DB::transaction(function () use ($materialId, $quantity, $branchId) {
                $material = Material::findOrFail($materialId);

                $stock = MaterialBranchStock::where('material_id', $materialId)
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->firstOrCreate([
                        'material_id' => $materialId,
                        'branch_id' => $branchId,
                    ]);

                $oldStock = (float) $stock->current_stock;
                $newStock = $oldStock - $quantity;

                $stock->current_stock = $newStock;
                $stock->save();

                StockLedger::create([
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'material_id' => $materialId,
                    'logged_at' => now()->toIso8601String(),
                    'initial_stock' => $oldStock,
                    'added_stock' => 0.0,
                    'used_stock' => $quantity,
                    'wasted_stock' => 0.0,
                    'remaining_stock' => $newStock,
                    'notes' => "Printed order item stock deduction: -{$quantity}",
                ]);

                return $material;
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
}
