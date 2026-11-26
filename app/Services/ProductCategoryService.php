<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductCategoryService
{
    /**
     * Create a new product category.
     */
    public function createCategory(array $data): ProductCategory
    {
        $userId = auth()->id();
        $branchId = auth()->user()->branch_id ?? null;

        try {
            return DB::transaction(function () use ($data) {
                return ProductCategory::create([
                    'id' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'calculation_archetype' => $data['calculation_archetype'] ?? 'per_piece',
                    'allowed_attribute_types' => $data['allowed_attribute_types'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Product category creation failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing product category.
     */
    public function updateCategory(string $id, array $data): ProductCategory
    {
        $userId = auth()->id();
        $branchId = auth()->user()->branch_id ?? null;

        try {
            return DB::transaction(function () use ($id, $data) {
                $category = ProductCategory::findOrFail($id);
                $category->update([
                    'name' => $data['name'],
                ]);

                return $category;
            });
        } catch (\Throwable $e) {
            Log::error('Product category update failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'category_id' => $id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a product category.
     */
    public function deleteCategory(string $id): void
    {
        $userId = auth()->id();
        $branchId = auth()->user()->branch_id ?? null;

        try {
            DB::transaction(function () use ($id) {
                $category = ProductCategory::findOrFail($id);
                $category->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Product category deletion failed.', [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'category_id' => $id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
