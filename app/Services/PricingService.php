<?php

namespace App\Services;

use App\Exceptions\PricingNotFoundException;
use App\Models\AttributePricing;
use App\Models\MachinePricing;
use App\Models\Pricing;
use App\Models\Product;

class PricingService
{
    /**
     * Calculate quote for a given product and its configurations.
     *
     * @throws PricingNotFoundException
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

        // Fetch core pricing record matching product, material, customer tier type, and tiered min_qty
        $pricing = Pricing::where('product_id', $productId)
            ->where('material_id', $materialId)
            ->where('customer_type', $customerType)
            ->where('min_qty', '<=', $quantity)
            ->orderBy('min_qty', 'desc')
            ->first();

        if (! $pricing) {
            throw new PricingNotFoundException(
                "Pricing record not found for product: {$product->name} matching these parameters.",
                $params
            );
        }

        // Fetch additive machine pricing modifier if machine is selected
        $machinePrice = 0.0;
        $machineCost = 0.0;
        if (! empty($machineId)) {
            $machinePricing = MachinePricing::where('pricing_id', $pricing->id)
                ->where('machine_id', $machineId)
                ->first();
            if ($machinePricing) {
                $machinePrice = (float) $machinePricing->price;
                $machineCost = (float) $machinePricing->cost;
            }
        }

        // Fetch additive attribute modifier pricings (finishing, cut, display)
        $attributePriceSum = 0.0;
        $attributeCostSum = 0.0;
        $attributeIds = array_filter([$finishingId, $cutId, $displayId]);
        if (! empty($attributeIds)) {
            $attrPricings = AttributePricing::where('pricing_id', $pricing->id)
                ->whereIn('attribute_id', $attributeIds)
                ->get();
            foreach ($attrPricings as $ap) {
                $attributePriceSum += (float) $ap->price;
                $attributeCostSum += (float) $ap->cost;
            }
        }

        // Sum base pricing + modifier pricing
        $basePrice = (float) $pricing->price;
        $baseCost = (float) $pricing->cost;

        $unitPrice = $basePrice + $machinePrice + $attributePriceSum;
        $unitCost = $baseCost + $machineCost + $attributeCostSum;
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

        // Keep precision using float numbers for decimal currency support
        $subtotal = round($subtotal, 2);
        $cost = round($cost, 2);
        $profit = round($subtotal - $cost, 2);

        return [
            'pricing_id' => $pricing->id,
            'unit_price' => round($unitPrice, 2),
            'unit_cost' => round($unitCost, 2),
            'area' => $area,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'cost' => $cost,
            'profit' => $profit,
        ];
    }
}
