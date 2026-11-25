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

final class RestoreProduct
{
    public function execute(Product $product, User $actor): Product
    {
        try {
            $restored = DB::transaction(function () use ($product, $actor): Product {
                $product->restore();

                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'RESTORE',
                    'description' => "Memulihkan produk: {$product->name}",
                    'model_type' => Product::class,
                    'model_id' => $product->id,
                    'payload' => $product->toArray(),
                ]);

                return $product->fresh('category') ?? $product->load('category');
            });
        } catch (Throwable $exception) {
            Log::error('Product restoration failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'product_id' => $product->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $restored;
    }
}
