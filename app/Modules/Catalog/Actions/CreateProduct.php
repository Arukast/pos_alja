<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateProduct
{
    /**
     * @param  array{name: string, product_category_id: string, pricing_type: string, is_active?: bool}  $data
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

                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'CREATE',
                    'description' => "Membuat produk: {$product->name}",
                    'model_type' => Product::class,
                    'model_id' => $product->id,
                    'payload' => $product->toArray(),
                ]);

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

        return $product->load('category');
    }
}
