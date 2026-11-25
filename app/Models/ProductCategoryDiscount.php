<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategoryDiscount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_category_id',
        'min_qty',
        'discount_percent',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'min_qty' => 'integer',
        'discount_percent' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
