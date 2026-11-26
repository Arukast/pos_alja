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

final class CreateProduct
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, product_category_id: string, pricing_type: string, is_active?: bool, material_ids?: array<int, string>}  $data
     */
    public function execute(array $data, User $actor): Product
    {
        try {
            $product = DB::transaction(function () use ($data, $actor): Product {
                $product = Product::create([
                    'name' => $data['name'],
                    'product_category_id' => $data['product_category_id'],
                    'pricing_type' => $data['pricing_type'],
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

                if (array_key_exists('material_ids', $data)) {
                    $product->materials()->sync(array_values($data['material_ids']));
                }

                $materialIds = $product->materials()->pluck('materials.id')->values()->all();

                $this->audit->record(
                    $actor,
                    'CREATE',
                    "Membuat produk: {$product->name}",
                    $product,
                    array_merge($product->toArray(), ['material_ids' => $materialIds]),
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

        return $product->load(['category', 'materials']);
    }
}
