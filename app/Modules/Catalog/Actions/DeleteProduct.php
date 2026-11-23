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

final class DeleteProduct
{
    public function execute(Product $product, User $actor): void
    {
        try {
            DB::transaction(function () use ($product, $actor): void {
                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'DELETE',
                    'description' => "Menghapus sementara produk: {$product->name}",
                    'model_type' => Product::class,
                    'model_id' => $product->id,
                    'payload' => $product->toArray(),
                ]);

                $product->delete();
            });
        } catch (Throwable $exception) {
            Log::error('Product deletion failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'product_id' => $product->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);
    }
}
