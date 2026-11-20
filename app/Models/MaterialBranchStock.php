<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $material_id
 * @property string $branch_id
 * @property string|float $current_stock
 * @property string|float $min_stock
 * @property-read Material $material
 * @property-read Branch $branch
 */
class MaterialBranchStock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'material_branch_stock';

    protected $fillable = [
        'material_id',
        'branch_id',
        'current_stock',
        'min_stock',
    ];

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
