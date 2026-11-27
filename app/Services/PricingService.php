<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PricingNotFoundException;
use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Pricing;
use App\Models\Product;
use InvalidArgumentException;

class PricingService
{
    /**
     * Calculate quote for a given product and its configurations.
     *
     * @throws PricingNotFoundException
     * @throws InvalidArgumentException
     */
    public function calculateQuote(array $params): array
    {
        $productId = $params['product_id'] ?? null;
        $materialId = $params['material_id'] ?? null;
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

        $branchId = $params['branch_id'] ?? null;

        $isFellBackToReguler = false;

        // Fetch core pricing record matching product, material, customer tier type, and tiered min_qty
        $pricing = Pricing::where('product_id', $productId)
            ->where('material_id', $materialId)
            ->where('customer_type', $customerType)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('min_qty', '<=', $quantity)
            ->orderBy('min_qty', 'desc')
            ->first();

        // Fallback to 'Reguler' pricing tier if custom type pricing is not defined
        if (! $pricing && $customerType !== 'Reguler') {
            $pricing = Pricing::where('product_id', $productId)
                ->where('material_id', $materialId)
                ->where('customer_type', 'Reguler')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('min_qty', '<=', $quantity)
                ->orderBy('min_qty', 'desc')
                ->first();
            if ($pricing) {
                $isFellBackToReguler = true;
            }
        }

        if (! $pricing) {
            throw new PricingNotFoundException(
                "Data harga untuk produk: {$product->name} dengan parameter ini tidak ditemukan.",
                $params
            );
        }

        // Fetch additive attribute pricings: a pricing-specific modifier row wins (including 0),
        // otherwise the attribute's own price/cost applies. selected_attributes is canonical;
        // legacy single-ID params remain for direct callers with no selection.
        $attributePriceSum = 0.0;
        $attributeCostSum = 0.0;

        $selectedAttributes = $params['selected_attributes'] ?? [];

        if (! is_array($selectedAttributes)) {
            $selectedAttributes = [];
        }

        if ($selectedAttributes === []) {
            // Support backward compatibility or direct ID pass in params
            $selectedAttributes = array_filter([$finishingId, $cutId, $displayId]);
        }

        // Request UUIDs may be uppercase while database IDs are lowercase.
        $normalizedAttributes = [];

        foreach ($selectedAttributes as $attributeId) {
            if (is_string($attributeId) && $attributeId !== '') {
                $normalizedAttributes[] = strtolower($attributeId);
            }
        }

        $selectedAttributes = array_values(array_unique($normalizedAttributes));

        if ($selectedAttributes !== []) {
            $modifiers = AttributePricing::where('pricing_id', $pricing->id)
                ->whereIn('attribute_id', $selectedAttributes)
                ->get()
                ->keyBy('attribute_id');

            $attributes = Attribute::whereIn('id', $selectedAttributes)
                ->get()
                ->keyBy('id');

            foreach ($selectedAttributes as $attributeId) {
                $modifier = $modifiers->get($attributeId);

                if ($modifier !== null) {
                    $attributePriceSum += (float) $modifier->price;
                    $attributeCostSum += (float) $modifier->cost;

                    continue;
                }

                $attribute = $attributes->get($attributeId);

                if ($attribute !== null) {
                    $attributePriceSum += (float) $attribute->price;
                    $attributeCostSum += (float) $attribute->cost;
                }
            }
        }

        // Sum base pricing + modifier pricing
        $basePrice = (float) $pricing->price;
        $baseCost = ((float) $pricing->cost > 0)
            ? (float) $pricing->cost
            : ($pricing->material ? (float) $pricing->material->cost : 0.0);

        if ($isFellBackToReguler && $customerType === 'Reseller') {
            $targetBranchId = $branchId ?: (auth()->check() ? auth()->user()->branch_id : null) ?: Branch::value('id');
            if ($targetBranchId) {
                $branchObj = Branch::find($targetBranchId);
                if ($branchObj) {
                    $discountPercent = $branchObj->getResellerDiscountPercent();
                    $basePrice = $basePrice * (1.0 - ($discountPercent / 100.0));
                }
            }
        }

        $unitPrice = $basePrice + $attributePriceSum;
        $unitCost = $baseCost + $attributeCostSum;
        $area = 0.0;

        if ($product->pricing_type === 'area') {
            if ($width <= 0 || $length <= 0) {
                throw new InvalidArgumentException('Lebar dan panjang harus lebih besar dari nol untuk perhitungan luas.');
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
