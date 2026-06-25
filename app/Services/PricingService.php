<?php

namespace App\Services;

use App\Exceptions\PricingMatrixNotFoundException;
use App\Models\PricingMatrix;

class PricingService
{
    /**
     * Calculate quote for a given product and its configurations.
     *
     * @throws PricingMatrixNotFoundException
     */
    public function calculateQuote(array $params): array
    {
        $productId = (int) ($params['product_id'] ?? 0);
        $materialId = (int) ($params['material_id'] ?? 0);
        $machineId = (int) ($params['machine_id'] ?? 0);
        $finishingId = (int) ($params['finishing_id'] ?? 0);
        $cutId = (int) ($params['cut_id'] ?? 0);
        $displayId = (int) ($params['display_id'] ?? 0);
        $sides = (int) ($params['sides'] ?? 1);
        $finishingSides = (int) ($params['finishing_sides'] ?? 0);
        $quantity = (float) ($params['quantity'] ?? 1);
        $width = (float) ($params['width'] ?? 0);
        $length = (float) ($params['length'] ?? 0);

        // Build base query
        $query = PricingMatrix::query()
            ->where('product_id', $productId)
            ->where('material_id', $materialId);

        // Filter based on product category (mapping legacy wo_hitung_harga.php queries)
        switch ($productId) {
            case 1: // Merchandise
                $query->where('machine_id', $machineId)
                    ->where('finishing_id', $finishingId)
                    ->where('sides', $sides)
                    ->where('finishing_sides', $finishingSides);
                break;

            case 2: // Jasa Desain
                $query->where('machine_id', $machineId);
                break;

            case 3: // Display
                $query->where('machine_id', $machineId)
                    ->where('display_id', $displayId);
                break;

            case 4: // Print Kertas
                $query->where('machine_id', $machineId)
                    ->where('sides', $sides)
                    ->where('finishing_id', $finishingId)
                    ->where('finishing_sides', $finishingSides)
                    ->where('cut_id', $cutId);
                break;

            case 5: // Large Format
                $query->where('machine_id', $machineId)
                    ->where('finishing_id', $finishingId);
                break;

            case 6: // ID Card
                $query->where('machine_id', $machineId)
                    ->where('display_id', $displayId)
                    ->where('sides', $sides);
                break;

            case 7: // Stempel
                $query->where('machine_id', $machineId)
                    ->where('finishing_id', $finishingId)
                    ->where('sides', $sides);
                break;

            case 8: // Kartu Nama
                $query->where('machine_id', $machineId)
                    ->where('finishing_id', $finishingId)
                    ->where('sides', $sides)
                    ->where('finishing_sides', $finishingSides)
                    ->where('display_id', $displayId);
                break;

            case 9: // Produk Jadi
                $query->where('machine_id', $machineId);
                break;

            case 10: // NOTA
                $query->where('machine_id', $machineId)
                    ->where('sides', $sides);
                break;

            case 11: // DTF Large Format
                $query->where('machine_id', $machineId);
                break;

            default:
                throw new PricingMatrixNotFoundException(
                    "Invalid product category ID: {$productId}",
                    $params
                );
        }

        $matrix = $query->first();

        if (! $matrix) {
            throw new PricingMatrixNotFoundException(
                "Pricing matrix record not found for product ID: {$productId} matching these parameters.",
                $params
            );
        }

        // Calculate totals
        $unitPrice = (int) $matrix->price;
        $unitCost = (int) $matrix->cost;
        $area = 0.0;

        if ($productId === 5) {
            // Large Format (banners) multiplies by area in square meters (panjang * lebar)
            if ($width <= 0 || $length <= 0) {
                $width = 1.0;
                $length = 1.0;
            }
            $area = $width * $length;
            $subtotal = $unitPrice * $quantity * $area;
            $cost = $unitCost * $quantity * $area;
        } else {
            $subtotal = $unitPrice * $quantity;
            $cost = $unitCost * $quantity;
        }

        // Standard round values to integers for currency
        $subtotal = (int) round($subtotal);
        $cost = (int) round($cost);
        $profit = $subtotal - $cost;

        return [
            'pricing_matrix_id' => $matrix->id,
            'unit_price' => $unitPrice,
            'unit_cost' => $unitCost,
            'area' => $area,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'cost' => $cost,
            'profit' => $profit,
        ];
    }
}
