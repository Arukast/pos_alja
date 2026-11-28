<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductQuantityDiscount;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class QuotePricingService
{
    /**
     * Calculate the authoritative quote for a product configuration.
     *
     * base_price = product.price + materials.price + Σ selected active attributes.price
     * reseller%  = customer_type === 'Reseller' ? Branch::find(branch_id)?->getResellerDiscountPercent() ?? 0 : 0
     * base_after_reseller = base_price * (1 - reseller%/100)
     * area_factor = product.selling_unit === 'm2' ? width * length : 1
     * gross_subtotal = base_price * quantity * area_factor
     * undiscounted_after_reseller = base_after_reseller * quantity * area_factor
     * tier% = highest ProductQuantityDiscount where product_id = … and min_qty <= quantity, else 0
     * subtotal = undiscounted_after_reseller * (1 - tier%/100)
     * unit_cost = product.cost + materials.cost + Σ selected active attributes.cost
     * cost = unit_cost * quantity * area_factor                        // no discount applied to cost
     * profit = subtotal - cost
     * discount_amount = gross_subtotal - subtotal                      // reseller + tier combined
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function calculate(array $params): array
    {
        $productId = is_string($params['product_id'] ?? null) && $params['product_id'] !== ''
            ? strtolower($params['product_id'])
            : '';
        $materialId = is_string($params['material_id'] ?? null) && $params['material_id'] !== ''
            ? strtolower($params['material_id'])
            : null;
        $branchId = is_string($params['branch_id'] ?? null) && $params['branch_id'] !== ''
            ? strtolower($params['branch_id'])
            : null;
        $customerType = is_string($params['customer_type'] ?? null) && $params['customer_type'] !== ''
            ? $params['customer_type']
            : 'Reguler';
        $quantity = (float) ($params['quantity'] ?? 1);
        $width = (float) ($params['width'] ?? 0);
        $length = (float) ($params['length'] ?? 0);

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih besar dari nol.');
        }

        $product = Product::query()->find($productId);

        if (! $product instanceof Product || ! $product->is_active) {
            throw new InvalidArgumentException('Produk tidak aktif atau tidak ditemukan.');
        }

        $materialPrice = 0.0;
        $materialCost = 0.0;

        if ($materialId !== null) {
            $material = Material::findOrFail($materialId);
            $materialPrice = (float) $material->price;
            $materialCost = (float) $material->cost;
        }

        $selectedIds = $params['selected_attributes'] ?? [];
        $selectedIds = is_array($selectedIds)
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $selectedIds
            ))))
            : [];

        // Internal allowance lets an unchanged order line keep attributes that have since been deactivated.
        $allowance = $params['inactive_attribute_allowance'] ?? [];
        $allowance = is_array($allowance)
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $allowance
            ))))
            : [];

        $attributes = $selectedIds === []
            ? collect()
            : Attribute::query()
                ->whereIn('id', $selectedIds)
                ->where(static function (Builder $query) use ($allowance): void {
                    $query->where('is_active', true)->orWhereIn('id', $allowance);
                })
                ->get();

        if ($attributes->count() !== count($selectedIds)) {
            throw new InvalidArgumentException('Atribut tidak aktif atau tidak ditemukan.');
        }

        $resellerPercent = 0.0;

        if ($customerType === 'Reseller' && $branchId !== null) {
            $branch = Branch::find($branchId);
            $resellerPercent = $branch?->getResellerDiscountPercent() ?? 0.0;
        }

        if ($resellerPercent < 0 || $resellerPercent > 100) {
            throw new InvalidArgumentException('Persentase diskon reseller harus antara 0 dan 100.');
        }

        $tier = ProductQuantityDiscount::query()
            ->where('product_id', $productId)
            ->where('min_qty', '<=', $quantity)
            ->orderByDesc('min_qty')
            ->first();

        $tierPercent = $tier === null ? 0.0 : (float) $tier->discount_percent;

        if ($tierPercent < 0 || $tierPercent > 100) {
            throw new InvalidArgumentException('Persentase diskon jumlah harus antara 0 dan 100.');
        }

        $attributePriceSum = 0.0;
        $attributeCostSum = 0.0;

        foreach ($attributes as $attribute) {
            $attributePriceSum += (float) $attribute->price;
            $attributeCostSum += (float) $attribute->cost;
        }

        $basePrice = (float) $product->price + $materialPrice + $attributePriceSum;
        $baseAfterReseller = $basePrice * (1.0 - ($resellerPercent / 100.0));
        $unitCost = (float) $product->cost + $materialCost + $attributeCostSum;
        $area = 0.0;
        $areaFactor = 1.0;

        if ($product->selling_unit === 'm2') {
            if ($width <= 0 || $length <= 0) {
                throw new InvalidArgumentException('Lebar dan panjang harus lebih besar dari nol untuk perhitungan luas.');
            }

            $area = $width * $length;
            $areaFactor = $area;
        }

        $grossSubtotal = $basePrice * $quantity * $areaFactor;
        $undiscountedAfterReseller = $baseAfterReseller * $quantity * $areaFactor;
        $subtotal = $undiscountedAfterReseller * (1.0 - ($tierPercent / 100.0));
        $cost = $unitCost * $quantity * $areaFactor;
        $profit = $subtotal - $cost;
        $discountAmount = $grossSubtotal - $subtotal;

        return [
            'product_id' => $productId,
            'material_id' => $materialId,
            'customer_type' => $customerType,
            'unit_price' => Decimal::money($baseAfterReseller),
            'unit_cost' => Decimal::money($unitCost),
            'area' => $area,
            'quantity' => $quantity,
            'reseller_discount_percent' => Decimal::money($resellerPercent),
            'quantity_discount_percent' => Decimal::money($tierPercent),
            'discount_amount' => Decimal::money($discountAmount),
            'subtotal' => Decimal::money($subtotal),
            'cost' => Decimal::money($cost),
            'profit' => Decimal::money($profit),
        ];
    }
}
