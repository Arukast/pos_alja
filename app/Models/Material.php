<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'product_category_id',
        'type',
        'dimensions',
        'unit',
        'cost',
        'price',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Limits materials to globally active rows and, when given, an active stock row in the branch.
     */
    public function scopeAvailableInBranch(Builder $query, ?string $branchId): Builder
    {
        $query->where('is_active', true);

        if ($branchId !== null) {
            $query->whereHas(
                'branchStocks',
                fn (Builder $stocks) => $stocks
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
            );
        }

        return $query;
    }

    /**
     * @return HasMany<MaterialBranchStock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(MaterialBranchStock::class);
    }

    /**
     * @return HasMany<MaterialBranchStock, $this>
     */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(MaterialBranchStock::class, 'material_id');
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
