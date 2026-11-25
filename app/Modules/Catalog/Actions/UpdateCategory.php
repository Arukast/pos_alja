<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateCategory
{
    /**
     * @param  array{
     *     name: string,
     *     calculation_archetype: string,
     *     allowed_attribute_types?: array<int, string>|null,
     *     discount_tiers?: array<int, array{min_qty: int|string, discount_percent: int|float|string}>
     * }  $data
     */
    public function execute(ProductCategory $category, array $data, User $actor): ProductCategory
    {
        try {
            $updated = DB::transaction(function () use ($category, $data): ProductCategory {
                $category->update([
                    'name' => $data['name'],
                    'calculation_archetype' => $data['calculation_archetype'],
                    'allowed_attribute_types' => $data['allowed_attribute_types'] ?? null,
                ]);

                if (array_key_exists('discount_tiers', $data)) {
                    $category->discountTiers()->delete();
                    $tiers = $data['discount_tiers'];

                    if ($tiers !== []) {
                        $category->discountTiers()->createMany(array_map(
                            static fn (array $tier): array => [
                                'min_qty' => (int) $tier['min_qty'],
                                'discount_percent' => $tier['discount_percent'],
                            ],
                            $tiers
                        ));
                    }
                }

                return $category;
            });
        } catch (Throwable $exception) {
            Log::error('Product category update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'category_id' => $category->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $updated->fresh('discountTiers') ?? $updated->load('discountTiers');
    }
}
