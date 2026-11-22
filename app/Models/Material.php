<?php

declare(strict_types=1);

namespace App\Models;

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
        'cost',
        'price',
    ];

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
