<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Product;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateProduct
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, product_category_id: string, pricing_type: string, is_active?: bool, material_ids?: array<int, string>}  $data
     */
    public function execute(Product $product, array $data, User $actor): Product
    {
        try {
            $updated = DB::transaction(function () use ($product, $data, $actor): Product {
                $before = array_merge($product->toArray(), [
                    'material_ids' => $product->materials()->pluck('materials.id')->values()->all(),
                ]);

                $product->update([
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'pricing_type' => $data['pricing_type'],
                    'is_active' => array_key_exists('is_active', $data)
                        ? (bool) $data['is_active']
                        : (bool) $product->is_active,
                ]);

                if (array_key_exists('material_ids', $data)) {
                    $product->materials()->sync(array_values($data['material_ids']));
                }

                $after = array_merge($product->fresh()?->toArray() ?? $product->toArray(), [
                    'material_ids' => $product->materials()->pluck('materials.id')->values()->all(),
                ]);

                $this->audit->record(
                    $actor,
                    'UPDATE',
                    "Memperbarui produk: {$product->name}",
                    $product,
                    [
                        'before' => $before,
                        'after' => $after,
                    ],
                );

                return $product;
            });
        } catch (Throwable $exception) {
            Log::error('Product update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'product_id' => $product->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $updated->load(['category', 'materials']);
    }
}
