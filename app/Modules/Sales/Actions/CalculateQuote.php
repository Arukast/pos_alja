<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Attribute;
use App\Models\Pricing;
use App\Services\PricingService;

final class CalculateQuote
{
    public function __construct(private readonly PricingService $pricingService) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function execute(array $params): array
    {
        $selected = $params['selected_attributes'] ?? [];

        if (is_array($selected) && $selected !== []) {
            $attributes = Attribute::query()
                ->whereIn('id', $selected)
                ->get()
                ->keyBy('type');

            $params['finishing_id'] = $attributes->get('finishing')?->id;
            $params['cut_id'] = $attributes->get('cut')?->id;
            $params['display_id'] = $attributes->get('display')?->id;
        }

        return $this->pricingService->calculateQuote($params);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
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
