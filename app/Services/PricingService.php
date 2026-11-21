<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PricingNotFoundException;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Machine;
use App\Models\MachinePricing;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
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
        $category = ProductCategory::findOrFail($product->product_category_id);

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
                "Pricing record not found for product: {$product->name} matching these parameters.",
                $params
            );
        }

        // Fetch additive machine pricing modifier if machine is selected
        $machinePrice = 0.0;
        $machineCost = 0.0;
        if (! empty($machineId)) {
            $machineBranchId = $branchId ?: (auth()->check() ? auth()->user()->branch_id : null);
            $machine = $machineBranchId
                ? Machine::query()->where('branch_id', $machineBranchId)->find($machineId)
                : null;

            if ($machine) {
                $machineCost = (float) $machine->cost;

                $machinePricing = MachinePricing::where('pricing_id', $pricing->id)
                    ->where('machine_id', $machineId)
                    ->first();

                if ($machinePricing) {
                    $machinePrice = (float) $machinePricing->price;

                    if ((float) $machinePricing->cost > 0.0) {
                        $machineCost = (float) $machinePricing->cost;
                    }
                }
            }
        }

        // Fetch additive attribute modifier pricings dynamically using selected_attributes list
        $attributePriceSum = 0.0;
        $attributeCostSum = 0.0;

        $selectedAttributes = $params['selected_attributes'] ?? [];
        if (empty($selectedAttributes)) {
            // Support backward compatibility or direct ID pass in params
            $selectedAttributes = array_filter([$finishingId, $cutId, $displayId]);
        }

        if (! empty($selectedAttributes)) {
            $attrPricings = AttributePricing::where('pricing_id', $pricing->id)
                ->whereIn('attribute_id', $selectedAttributes)
                ->get();
            foreach ($attrPricings as $ap) {
                $attributePriceSum += (float) $ap->price;
                $attributeCostSum += (float) $ap->cost;
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

        $unitPrice = $basePrice + $machinePrice + $attributePriceSum;
        $unitCost = $baseCost + $machineCost + $attributeCostSum;
        $area = 0.0;

        switch ($category->calculation_archetype) {
            case 'surface_area':
                if ($width <= 0 || $length <= 0) {
                    throw new InvalidArgumentException('Width and length must be greater than zero for surface area calculation.');
                }
                $area = $width * $length;
                $subtotal = $unitPrice * $quantity * $area;
                $cost = $unitCost * $quantity * $area;
                break;

            case 'linear_dimension':
                if ($length <= 0) {
                    throw new InvalidArgumentException('Length must be greater than zero for linear dimension calculation.');
                }
                $subtotal = $unitPrice * $quantity * $length;
                $cost = $unitCost * $quantity * $length;
                break;

            case 'tiered_volume':
                // The query above selects the correct tiered pricing row by min_qty lookup.
                // We evaluate the unit price dynamically matching the quantity tier.
                $subtotal = $unitPrice * $quantity;
                $cost = $unitCost * $quantity;
                break;

            case 'per_piece':
            default:
                $subtotal = $unitPrice * $quantity;
                $cost = $unitCost * $quantity;
                break;
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
