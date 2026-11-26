<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteCategory
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(ProductCategory $category, User $actor): void
    {
        try {
            DB::transaction(function () use ($category, $actor): void {
                $this->audit->record(
                    $actor,
                    'DELETE',
                    "Menghapus kategori: {$category->name}",
                    $category,
                    [
                        'before' => $category->auditSnapshot(),
                        'after' => null,
                    ],
                );

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
