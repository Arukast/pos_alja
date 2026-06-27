<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pricing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pricing';

    protected $fillable = [
        'branch_id',
        'product_id',
        'material_id',
        'customer_type',
        'min_qty',
        'price',
        'cost',
        'profit',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function attributePricings(): HasMany
    {
        return $this->hasMany(AttributePricing::class);
    }

    public function machinePricings(): HasMany
    {
        return $this->hasMany(MachinePricing::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
