<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CreateCategory
{
    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     is_active?: bool
     * }  $data
     */
    public function execute(array $data, User $actor): ProductCategory
    {
        try {
            $category = DB::transaction(function () use ($data): ProductCategory {
                return ProductCategory::create([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'calculation_archetype' => 'per_piece',
                    'allowed_attribute_types' => null,
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Product category creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        SafeBroadcast::orderUpdated($actor->branch_id, $actor);

        return $category;
    }
}
