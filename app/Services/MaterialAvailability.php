<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Material;

/**
 * Resolves whether a material can be used for new quote and invoice lines in a branch.
 */
class MaterialAvailability
{
    /**
     * A material is available when it is globally active and has an active stock row in the branch.
     */
    public function isAvailable(?Material $material, string $branchId): bool
    {
        if (! $material instanceof Material || ! $material->is_active || $material->trashed()) {
            return false;
        }

        return Material::query()
            ->whereKey($material->id)
            ->availableInBranch($branchId)
            ->exists();
    }
}
