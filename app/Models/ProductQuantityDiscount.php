<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuantityDiscount extends Model
{
    use HasUuids;

    protected $table = 'product_quantity_discounts';

    protected $fillable = [
        'product_id',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
