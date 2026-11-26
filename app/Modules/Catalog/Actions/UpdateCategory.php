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

final class UpdateCategory
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     is_active?: bool
     * }  $data
     */
    public function execute(ProductCategory $category, array $data, User $actor): ProductCategory
    {
        try {
            $updated = DB::transaction(function () use ($category, $data, $actor): ProductCategory {
                $before = $category->auditSnapshot();

                $category->update([
                    'name' => $data['name'],
                    'description' => array_key_exists('description', $data)
                        ? $data['description']
                        : $category->description,
                    'is_active' => array_key_exists('is_active', $data)
                        ? (bool) $data['is_active']
                        : (bool) $category->is_active,
                ]);

                $this->audit->record(
                    $actor,
                    'UPDATE',
                    "Memperbarui kategori: {$category->name}",
                    $category,
                    [
                        'before' => $before,
                        'after' => $category->auditSnapshot(),
                    ],
                );

                return $category;
            });
        } catch (Throwable $exception) {
            Log::error('Product category update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'category_id' => $category->id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $updated->fresh() ?? $updated;
    }
}
