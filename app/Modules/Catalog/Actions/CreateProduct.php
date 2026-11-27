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

final class CreateProduct
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductComposition $composition,
    ) {}

    /**
     * @param  array{name: string, product_category_id: string, pricing_type: string, is_active?: bool, requires_material?: bool, uses_attributes?: bool, material_ids?: array<int, string>, attribute_type_ids?: array<int, string>, attribute_ids?: array<int, string>}  $data
     */
    public function execute(array $data, User $actor): Product
    {
        $this->composition->validate($data);

        try {
            $product = DB::transaction(function () use ($data, $actor): Product {
                $product = Product::create([
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'pricing_type' => $data['pricing_type'],
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'requires_material' => (bool) ($data['requires_material'] ?? true),
                    'uses_attributes' => (bool) ($data['uses_attributes'] ?? true),
                ]);

                $this->composition->sync($product, $data);

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

        return $product->load(['category', 'materials', 'attributeTypes', 'compatibleAttributes.typeInfo']);
    }
}
