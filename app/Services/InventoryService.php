<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Material;
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
            // Update material stock levels
            $material->initial_stock = (float) $material->remaining_stock;
            $material->added_stock = $quantity;
            $material->current_stock = $material->initial_stock + $quantity;

            // Recalculate remaining stock: current - sold - wasted
            $material->remaining_stock = $material->current_stock - (float) $material->sold_stock - (float) $material->wasted_stock;
            $material->save();

            // Log in stock_ledgers
            DB::table('stock_ledgers')->insert([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'material_id' => $material->id,
                'logged_at' => now()->toDateString(),
                'initial_stock' => $material->initial_stock,
                'added_stock' => $quantity,
                'used_stock' => (float) $material->sold_stock,
                'wasted_stock' => (float) $material->wasted_stock,
                'remaining_stock' => $material->remaining_stock,
                'notes' => $notes ?: "Manual stock addition: +{$quantity}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $material;
        });
    }

    /**
     * Log print waste/failure, increment wasted counter, and decrement remaining stock.
     */
    public function logWaste(array $params): WastedMaterial
    {
        return DB::transaction(function () use ($params) {
            $materialId = $params['material_id'];
            $quantity = (float) $params['quantity'];
            $machineId = ! empty($params['machine_id']) ? $params['machine_id'] : null;
            $dimensions = $params['dimensions'] ?? null;
            $designerName = $params['designer_name'] ?? null;
            $operatorName = $params['operator_name'] ?? null;
            $notes = $params['notes'] ?? 'Print failure';

            // Load parent material first to get its branch scoping
            $material = Material::findOrFail($materialId);

            // 1. Create wasted material record
            $waste = WastedMaterial::create([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'logged_at' => now()->toDateString(),
                'material_id' => $materialId,
                'machine_id' => $machineId,
                'quantity' => $quantity,
                'dimensions' => $dimensions,
                'designer_name' => $designerName,
                'operator_name' => $operatorName,
                'notes' => $notes,
            ]);

            // 2. Adjust parent material stock counters
            $material->wasted_stock = (float) $material->wasted_stock + $quantity;
            $material->remaining_stock = (float) $material->current_stock - (float) $material->sold_stock - $material->wasted_stock;
            $material->save();

            // 3. Log in stock_ledgers
            DB::table('stock_ledgers')->insert([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'material_id' => $material->id,
                'logged_at' => now()->toDateString(),
                'initial_stock' => (float) $material->initial_stock,
                'added_stock' => (float) $material->added_stock,
                'used_stock' => (float) $material->sold_stock,
                'wasted_stock' => $material->wasted_stock,
                'remaining_stock' => $material->remaining_stock,
                'notes' => "Failed Print logged: -{$quantity} ({$notes})",
                'created_at' => now(),
                'updated_at' => now(),
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
            $material = Material::findOrFail($materialId);
            $material->sold_stock = (float) $material->sold_stock + $quantity;
            $material->remaining_stock = (float) $material->current_stock - $material->sold_stock - (float) $material->wasted_stock;
            $material->save();

            // Log in stock ledgers
            DB::table('stock_ledgers')->insert([
                'branch_id' => $material->branch_id ?: Branch::value('id'),
                'material_id' => $material->id,
                'logged_at' => now()->toDateString(),
                'initial_stock' => (float) $material->initial_stock,
                'added_stock' => (float) $material->added_stock,
                'used_stock' => $material->sold_stock,
                'wasted_stock' => (float) $material->wasted_stock,
                'remaining_stock' => $material->remaining_stock,
                'notes' => "Printed order item stock deduction: -{$quantity}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $material;
        });
    }
}
