<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Product;
use App\Models\ProductQuantityDiscount;
use App\Models\User;
use App\Modules\Catalog\Support\ProductComposition;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateProduct
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductComposition $composition,
    ) {}

    /**
     * @param  array{name: string, product_category_id: string, selling_unit: string, price?: string, cost?: string, is_active?: bool, requires_material?: bool, uses_attributes?: bool, material_ids?: array<int, string>, attribute_type_ids?: array<int, string>, attribute_ids?: array<int, string>, tiers?: array<int, array{id?: string, min_qty: int, discount_percent: string}>}  $data
     */
    public function execute(array $data, User $actor): Product
    {
        $this->composition->validate($data);

        try {
            $product = DB::transaction(function () use ($data, $actor): Product {
                $product = Product::create([
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'selling_unit' => $data['selling_unit'],
                    'price' => $data['price'] ?? '0.00',
                    'cost' => $data['cost'] ?? '0.00',
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'requires_material' => (bool) ($data['requires_material'] ?? true),
                    'uses_attributes' => (bool) ($data['uses_attributes'] ?? true),
                ]);

                $this->composition->sync($product, $data);

                foreach ($data['tiers'] ?? [] as $tier) {
                    ProductQuantityDiscount::query()->create([
                        'product_id' => $product->id,
                        'min_qty' => (int) $tier['min_qty'],
                        'discount_percent' => $tier['discount_percent'],
                    ]);
                }

                $this->audit->record(
                    $actor,
                    'CREATE',
                    "Membuat produk: {$product->name}",
                    $product,
                    array_merge($product->toArray(), $product->auditSnapshot()),
                );

                return $product;
            });
        } catch (Throwable $exception) {
            Log::error('Product creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $product->load(['category', 'materials', 'attributeTypes', 'compatibleAttributes.typeInfo', 'quantityDiscounts']);
    }
}
