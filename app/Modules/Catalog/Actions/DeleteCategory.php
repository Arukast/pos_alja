<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteCategory
{
    public function execute(ProductCategory $category, User $actor): void
    {
        try {
            DB::transaction(function () use ($category): void {
                $category->delete();
            });
        } catch (Throwable $exception) {
            Log::error('Product category deletion failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'category_id' => $category->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);
    }
}
