<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

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

        $selected = $params['selected_attributes'] ?? [];

        if (is_array($selected) && $selected !== []) {
            $selectedIds = array_values(array_unique(array_filter(
                $selected,
                static fn (mixed $id): bool => is_string($id) && $id !== ''
            )));

            if ($selectedIds !== []) {
                $branchId = is_string($params['branch_id'] ?? null) && $params['branch_id'] !== ''
                    ? $params['branch_id']
                    : null;

                $attributes = Attribute::query()
                    ->whereIn('id', $selectedIds)
                    ->when($branchId !== null, function (Builder $query) use ($branchId): void {
                        $query->where(function (Builder $scoped) use ($branchId): void {
                            $scoped->where('branch_id', $branchId)->orWhereNull('branch_id');
                        });
                    })
                    ->get();

                if ($attributes->count() !== count($selectedIds)) {
                    throw new InvalidArgumentException(
                        'Atribut finishing atau display tidak valid untuk cabang ini.'
                    );
                }

                $hasDuplicateType = $attributes
                    ->groupBy('type')
                    ->contains(static fn ($group): bool => $group->count() > 1);

                if ($hasDuplicateType) {
                    throw new InvalidArgumentException(
                        'Setiap item hanya dapat memilih satu opsi per jenis atribut.'
                    );
                }

                $byType = $attributes->keyBy('type');

                $params['finishing_id'] = $byType->get('finishing')?->id;
                $params['cut_id'] = $byType->get('cut')?->id;
                $params['display_id'] = $byType->get('display')?->id;
                $params['selected_attributes'] = array_values(array_filter([
                    $params['finishing_id'],
                    $params['cut_id'],
                    $params['display_id'],
                ]));
            }
        }

        return $this->pricingService->calculateQuote($params);
    }

    /**
     * Menghitung penawaran untuk baris harga tertentu pada pembuatan invoice.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     *
     * @throws PricingNotFoundException
     * @throws InvalidArgumentException
     */
    public function forPricing(
        Pricing $pricing,
        array $item,
        string $branchId,
        string $customerType
    ): array {
        return $this->execute([
            'product_id' => $pricing->product_id,
            'material_id' => $pricing->material_id,
            'machine_id' => $item['machine_id'] ?? null,
            'selected_attributes' => $item['selected_attributes'] ?? [],
            'quantity' => $item['quantity'] ?? 1,
            'width' => $item['width'] ?? 0,
            'length' => $item['length'] ?? 0,
            'customer_type' => $customerType,
            'branch_id' => $branchId,
        ]);
    }
}
