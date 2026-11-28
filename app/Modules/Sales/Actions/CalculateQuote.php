<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Exceptions\InvalidAttributeSelectionException;
use App\Exceptions\InvalidMaterialSelectionException;
use App\Exceptions\InvalidProductAttributeSelectionException;
use App\Models\Attribute;
use App\Models\Product;
use App\Services\QuotePricingService;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Menghitung penawaran harga item pesanan berdasarkan konfigurasi katalog dan pelanggan.
 */
final class CalculateQuote
{
    /**
     * Membuat instance aksi kalkulasi penawaran.
     */
    public function __construct(private readonly QuotePricingService $pricingService) {}

    /**
     * Menghitung harga satuan, biaya, luas, subtotal, dan profit dari parameter penawaran.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function execute(array $params): array
    {
        $customerType = $params['customer_type'] ?? null;
        $params['customer_type'] = is_string($customerType) && $customerType !== ''
            ? $customerType
            : 'Reguler';

        // Internal only: never populated from a request.
        $skipCapabilityChecks = ($params['skip_product_capability_checks'] ?? false) === true;

        $product = Product::query()
            ->with(['materials:id', 'attributeTypes:id,slug', 'compatibleAttributes:id'])
            ->find($params['product_id'] ?? null);

        if (! $product instanceof Product || ! $product->is_active) {
            throw new InvalidArgumentException('Produk tidak aktif atau tidak ditemukan.');
        }

        $materialId = $params['material_id'] ?? null;
        $materialId = is_string($materialId) && $materialId !== '' ? strtolower($materialId) : null;

        // Server-derived list of attribute IDs that an existing invoice may keep unchanged.
        $allowance = $params['inactive_attribute_allowance'] ?? [];
        $allowance = is_array($allowance)
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $allowance
            ))))
            : [];

        $selected = $params['selected_attributes'] ?? [];
        $selectedIds = is_array($selected) && $selected !== []
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $selected
            ))))
            : [];

        if (! $skipCapabilityChecks) {
            if ($product->requires_material && $materialId === null) {
                throw new InvalidMaterialSelectionException('Bahan wajib dipilih untuk produk ini.');
            }

            if (! $product->requires_material && $materialId !== null) {
                throw new InvalidMaterialSelectionException('Produk ini tidak menggunakan bahan.');
            }

            if ($materialId !== null
                && $product->materials->isNotEmpty()
                && ! $product->materials->contains('id', $materialId)) {
                throw new InvalidMaterialSelectionException('Bahan tidak kompatibel dengan produk ini.');
            }

            if (! $product->uses_attributes && $selectedIds !== []) {
                throw new InvalidProductAttributeSelectionException('Produk ini tidak menggunakan atribut.');
            }
        }

        if ($selectedIds !== []) {
            $branchId = is_string($params['branch_id'] ?? null) && $params['branch_id'] !== ''
                ? $params['branch_id']
                : null;

            $attributes = Attribute::query()
                ->whereIn('id', $selectedIds)
                ->with('typeInfo')
                ->when($branchId !== null, function (Builder $query) use ($branchId): void {
                    $query->where(function (Builder $scoped) use ($branchId): void {
                        $scoped->where('branch_id', $branchId)->orWhereNull('branch_id');
                    });
                })
                ->get();

            if ($attributes->count() !== count($selectedIds)) {
                throw new InvalidAttributeSelectionException(
                    'Atribut tidak valid untuk cabang ini.'
                );
            }

            $hasDuplicateType = $attributes
                ->groupBy('type')
                ->contains(static fn ($group): bool => $group->count() > 1);

            if ($hasDuplicateType) {
                throw new InvalidAttributeSelectionException(
                    'Setiap item hanya dapat memilih satu opsi per jenis atribut.'
                );
            }

            $allowedTypeSlugs = $product->attributeTypes->pluck('slug')->all();
            $allowedAttributeIds = $product->compatibleAttributes->pluck('id')->all();

            foreach ($attributes as $attribute) {
                $typeActive = $attribute->typeInfo?->is_active === true;

                if ((! $attribute->is_active || ! $typeActive)
                    && ! in_array($attribute->id, $allowance, true)) {
                    throw new InvalidAttributeSelectionException(
                        'Atribut tidak aktif atau jenis atributnya tidak aktif.'
                    );
                }

                if (! $skipCapabilityChecks) {
                    if ($allowedTypeSlugs !== [] && ! in_array($attribute->type, $allowedTypeSlugs, true)) {
                        throw new InvalidProductAttributeSelectionException(
                            'Atribut tidak diizinkan untuk produk ini.'
                        );
                    }

                    if ($allowedAttributeIds !== [] && ! in_array($attribute->id, $allowedAttributeIds, true)) {
                        throw new InvalidProductAttributeSelectionException(
                            'Atribut tidak diizinkan untuk produk ini.'
                        );
                    }
                }
            }

            $params['selected_attributes'] = $selectedIds;
        }

        return $this->pricingService->calculate($params);
    }
}
