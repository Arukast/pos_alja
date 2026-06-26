<?php

namespace App\Services;

use App\Exceptions\PricingMatrixNotFoundException;
use App\Models\PricingMatrix;
use App\Models\Product;

class PricingService
{
    /**
     * Calculate quote for a given product and its configurations.
     *
     * @throws PricingMatrixNotFoundException
     */
    public function calculateQuote(array $params): array
    {
        $productId = $params['product_id'] ?? null;
        $materialId = $params['material_id'] ?? null;
        $machineId = $params['machine_id'] ?? null;
        $finishingId = $params['finishing_id'] ?? null;
        $cutId = $params['cut_id'] ?? null;
        $displayId = $params['display_id'] ?? null;
        $sides = (int) ($params['sides'] ?? 1);
        $finishingSides = (int) ($params['finishing_sides'] ?? 0);
        $quantity = (float) ($params['quantity'] ?? 1);
        $width = (float) ($params['width'] ?? 0);
        $length = (float) ($params['length'] ?? 0);
        $customerType = $params['customer_type'] ?? 'Reguler';

        // Load product to check pricing type and name
        $product = Product::findOrFail($productId);

        // Build base query matching product, material and customer tier type
        $query = PricingMatrix::query()
            ->where('product_id', $productId)
            ->where('material_id', $materialId)
            ->where('customer_type', $customerType);

        // Match options, replacing 0/empty strings with null
        $machineUuid = ! empty($machineId) ? $machineId : null;
        $finishingUuid = ! empty($finishingId) ? $finishingId : null;
        $cutUuid = ! empty($cutId) ? $cutId : null;
        $displayUuid = ! empty($displayId) ? $displayId : null;

        $query->where('machine_id', $machineUuid);

        // Filter based on product category rules dynamically
        switch ($product->name) {
            case 'Merchandise':
                $query->where('finishing_id', $finishingUuid)
                    ->where('sides', $sides)
                    ->where('finishing_sides', $finishingSides);
                break;

            case 'Jasa Desain':
                break;

            case 'Display':
                $query->where('display_id', $displayUuid);
                break;

            case 'Print Kertas':
                $query->where('sides', $sides)
                    ->where('finishing_id', $finishingUuid)
                    ->where('finishing_sides', $finishingSides)
                    ->where('cut_id', $cutUuid);
                break;

            case 'Large Format':
                $query->where('finishing_id', $finishingUuid);
                break;

            case 'ID Card':
                $query->where('display_id', $displayUuid)
                    ->where('sides', $sides);
                break;

            case 'Stempel':
                $query->where('finishing_id', $finishingUuid)
                    ->where('sides', $sides);
                break;

            case 'Kartu Nama':
                $query->where('finishing_id', $finishingUuid)
                    ->where('sides', $sides)
                    ->where('finishing_sides', $finishingSides)
                    ->where('display_id', $displayUuid);
                break;

            case 'Produk Jadi':
                break;

            case 'NOTA':
                $query->where('sides', $sides);
                break;

            case 'DTF Large Format':
                break;

            default:
                // Fallback matching to handle arbitrary product categories
                if (! empty($finishingUuid)) {
                    $query->where('finishing_id', $finishingUuid);
                }
                if (! empty($cutUuid)) {
                    $query->where('cut_id', $cutUuid);
                }
                if (! empty($displayUuid)) {
                    $query->where('display_id', $displayUuid);
                }
                break;
        }

        // Apply automatic bulk tiered discount: fetch highest min_qty <= quantity ordered
        $matrix = $query->where('min_qty', '<=', $quantity)
            ->orderBy('min_qty', 'desc')
            ->first();

        if (! $matrix) {
            throw new PricingMatrixNotFoundException(
                "Pricing matrix record not found for product: {$product->name} matching these parameters.",
                $params
            );
        }

        // Calculate totals
        $unitPrice = (int) $matrix->price;
        $unitCost = (int) $matrix->cost;
        $area = 0.0;

        if ($product->pricing_type === 'area') {
            // Area-based pricing (panjang * lebar)
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

        // Round values to integers for currency
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
