<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Material;
use App\Models\StockLedger;
use App\Models\WastedMaterial;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Add new stock to material and log in stock ledger.
     */
    public function addStock(Material $material, float $quantity, ?string $notes = null): Material
    {
        return DB::transaction(function () use ($material, $quantity, $notes) {
            // Lock the material row for update to prevent concurrent updates race conditions
            $materialLocked = Material::where('id', $material->id)->lockForUpdate()->firstOrFail();
            $oldStock = (float) $materialLocked->current_stock;
            $newStock = $oldStock + $quantity;

            // Update cached current_stock
            $materialLocked->current_stock = $newStock;
            $materialLocked->save();

            // Log in stock_ledgers (retains historical stock counters)
            StockLedger::create([
                'branch_id' => $materialLocked->branch_id ?: Branch::value('id'),
                'material_id' => $materialLocked->id,
                'logged_at' => now()->toIso8601String(), // upgraded to TIMESTAMPTZ compatible string
                'initial_stock' => $oldStock,
                'added_stock' => $quantity,
                'used_stock' => 0.0,
                'wasted_stock' => 0.0,
                'remaining_stock' => $newStock,
                'notes' => $notes ?: "Manual stock addition: +{$quantity}",
            ]);

            return $materialLocked;
        });
    }

    /**
     * Log print waste/failure, decrement current stock cache, and write to stock ledger.
     */
    public function logWaste(array $params): WastedMaterial
    {
        return DB::transaction(function () use ($params) {
            $materialId = $params['material_id'];
            $quantity = (float) $params['quantity'];
            $machineId = ! empty($params['machine_id']) ? $params['machine_id'] : null;
            $dimensions = $params['dimensions'] ?? null;
            $designerId = $params['designer_id'] ?? null;
            $operatorId = $params['operator_id'] ?? null;
            $orderId = $params['order_id'] ?? null;
            $notes = $params['notes'] ?? 'Print failure';

            // Lock the material row for update to prevent concurrent updates race conditions
            $material = Material::where('id', $materialId)->lockForUpdate()->firstOrFail();
            $oldStock = (float) $material->current_stock;
            $newStock = $oldStock - $quantity;

            // 1. Create wasted material record
            $waste = WastedMaterial::create([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'logged_at' => now()->toIso8601String(), // upgraded to TIMESTAMPTZ compatible string
                'material_id' => $materialId,
                'machine_id' => $machineId,
                'order_id' => $orderId,
                'designer_id' => $designerId,
                'operator_id' => $operatorId,
                'quantity' => $quantity,
                'dimensions' => $dimensions,
                'notes' => $notes,
            ]);

            // 2. Adjust parent material stock counters
            $material->current_stock = $newStock;
            $material->save();

            // 3. Log in stock_ledgers
            StockLedger::create([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'material_id' => $material->id,
                'logged_at' => now()->toIso8601String(), // upgraded to TIMESTAMPTZ compatible string
                'initial_stock' => $oldStock,
                'added_stock' => 0.0,
                'used_stock' => 0.0,
                'wasted_stock' => $quantity,
                'remaining_stock' => $newStock,
                'notes' => "Failed Print logged: -{$quantity} ({$notes})",
            ]);

            return $waste;
        });
    }

    /**
     * Deduct stock for printed layout items.
     */
    public function deductStockOnPrint(string $materialId, float $quantity): Material
    {
        return DB::transaction(function () use ($materialId, $quantity) {
            // Lock the material row for update to prevent concurrent updates race conditions
            $material = Material::where('id', $materialId)->lockForUpdate()->firstOrFail();
            $oldStock = (float) $material->current_stock;
            $newStock = $oldStock - $quantity;

            $material->current_stock = $newStock;
            $material->save();

            // Log in stock ledgers
            StockLedger::create([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'material_id' => $material->id,
                'logged_at' => now()->toIso8601String(), // upgraded to TIMESTAMPTZ compatible string
                'initial_stock' => $oldStock,
                'added_stock' => 0.0,
                'used_stock' => $quantity,
                'wasted_stock' => 0.0,
                'remaining_stock' => $newStock,
                'notes' => "Printed order item stock deduction: -{$quantity}",
            ]);

            return $material;
        });
    }
}
