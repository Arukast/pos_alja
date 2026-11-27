<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Product;
use App\Models\User;
use App\Modules\Catalog\Support\ProductComposition;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateProduct
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductComposition $composition,
    ) {}

    /**
     * @param  array{name: string, product_category_id: string, pricing_type: string, is_active?: bool, requires_material?: bool, uses_attributes?: bool, material_ids?: array<int, string>, attribute_type_ids?: array<int, string>, attribute_ids?: array<int, string>}  $data
     */
    public function execute(Product $product, array $data, User $actor): Product
    {
        $this->composition->validate($data, $product);

        try {
            $updated = DB::transaction(function () use ($product, $data, $actor): Product {
                $before = array_merge($product->toArray(), $product->auditSnapshot());

                $product->update([
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'pricing_type' => $data['pricing_type'],
                    'is_active' => array_key_exists('is_active', $data)
                        ? (bool) $data['is_active']
                        : (bool) $product->is_active,
                    'requires_material' => array_key_exists('requires_material', $data)
                        ? (bool) $data['requires_material']
                        : (bool) $product->requires_material,
                    'uses_attributes' => array_key_exists('uses_attributes', $data)
                        ? (bool) $data['uses_attributes']
                        : (bool) $product->uses_attributes,
                ]);

                $this->composition->sync($product, $data);

                $fresh = $product->fresh() ?? $product;
                $after = array_merge($fresh->toArray(), $fresh->auditSnapshot());

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

        return $updated->load(['category', 'materials', 'attributeTypes', 'compatibleAttributes.typeInfo']);
    }
}
