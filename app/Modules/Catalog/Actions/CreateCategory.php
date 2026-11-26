<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateCategory
{
    /**
     * @param  array{
     *     name: string,
     *     discount_tiers?: array<int, array{min_qty: int|string, discount_percent: int|float|string}>
     * }  $data
     */
    public function execute(array $data, User $actor): ProductCategory
    {
        try {
            $category = DB::transaction(function () use ($data): ProductCategory {
                $category = ProductCategory::create([
                    'name' => $data['name'],
                    'calculation_archetype' => 'per_piece',
                    'allowed_attribute_types' => null,
                ]);

                $tiers = $data['discount_tiers'] ?? [];

                if ($tiers !== []) {
                    $category->discountTiers()->createMany(array_map(
                        static fn (array $tier): array => [
                            'min_qty' => (int) $tier['min_qty'],
                            'discount_percent' => $tier['discount_percent'],
                        ],
                        $tiers
                    ));
                }

                return $category;
            });
        } catch (Throwable $exception) {
            Log::error('Product category creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $category->load('discountTiers');
    }
}
