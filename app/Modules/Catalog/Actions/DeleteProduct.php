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

final class DeleteProduct
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(Product $product, User $actor): void
    {
        try {
            DB::transaction(function () use ($product, $actor): void {
                $this->audit->record(
                    $actor,
                    'DELETE',
                    "Menghapus sementara produk: {$product->name}",
                    $product,
                    $product->toArray(),
                );

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
