<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Material;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class SetMaterialActive
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(Material $material, bool $isActive, User $actor): Material
    {
        return DB::transaction(function () use ($material, $isActive, $actor): Material {
            $locked = Material::query()
                ->whereKey($material->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = ['is_active' => (bool) $locked->is_active];

            $locked->update(['is_active' => $isActive]);

            $this->audit->record(
                $actor,
                'UPDATE',
                "Memperbarui bahan: {$locked->name}",
                $locked,
                [
                    'before' => $before,
                    'after' => ['is_active' => (bool) $locked->is_active],
                ],
            );

            return $locked->refresh();
        });
    }
}
