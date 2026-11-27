<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Exceptions\InvalidAttributeSelectionException;
use App\Exceptions\PricingNotFoundException;
use App\Models\Attribute;
use App\Models\Pricing;
use App\Services\PricingService;
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
    public function __construct(private readonly PricingService $pricingService) {}

    /**
     * Menghitung harga satuan, biaya, luas, subtotal, dan profit dari parameter penawaran.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws PricingNotFoundException
     * @throws InvalidArgumentException
     */
    public function execute(array $params): array
    {
        $customerType = $params['customer_type'] ?? null;
        $params['customer_type'] = is_string($customerType) && $customerType !== ''
            ? $customerType
            : 'Reguler';

        // Server-derived list of attribute IDs that an existing invoice may keep unchanged.
        $allowance = $params['inactive_attribute_allowance'] ?? [];
        $allowance = is_array($allowance)
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $allowance
            ))))
            : [];

        $selected = $params['selected_attributes'] ?? [];

        if (is_array($selected) && $selected !== []) {
            $selectedIds = array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
                $selected
            ))));

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

                foreach ($attributes as $attribute) {
                    $typeActive = $attribute->typeInfo?->is_active === true;

                    if ((! $attribute->is_active || ! $typeActive)
                        && ! in_array($attribute->id, $allowance, true)) {
                        throw new InvalidAttributeSelectionException(
                            'Atribut tidak aktif atau jenis atributnya tidak aktif.'
                        );
                    }
                }

                $byType = $attributes->keyBy('type');

                $params['finishing_id'] = $byType->get('finishing')?->id;
                $params['cut_id'] = $byType->get('cut')?->id;
                $params['display_id'] = $byType->get('display')?->id;
                $params['selected_attributes'] = $selectedIds;
            }
        }

        return $this->pricingService->calculateQuote($params);
    }

    /**
     * Menghitung penawaran untuk baris harga tertentu pada pembuatan invoice.
     *
     * @param  array<string, mixed>  $item
     * @param  list<string>  $inactiveAttributeAllowance
     * @return array<string, mixed>
     *
     * @throws PricingNotFoundException
     * @throws InvalidArgumentException
     */
    public function forPricing(
        Pricing $pricing,
        array $item,
        string $branchId,
        string $customerType,
        array $inactiveAttributeAllowance = []
    ): array {
        return $this->execute([
            'product_id' => $pricing->product_id,
            'material_id' => $pricing->material_id,
            'selected_attributes' => $item['selected_attributes'] ?? [],
            'quantity' => $item['quantity'] ?? 1,
            'width' => $item['width'] ?? 0,
            'length' => $item['length'] ?? 0,
            'customer_type' => $customerType,
            'branch_id' => $branchId,
            'inactive_attribute_allowance' => $inactiveAttributeAllowance,
        ]);
    }
}
