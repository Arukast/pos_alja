<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function attributePricings()
    {
        return $this->hasMany(AttributePricing::class);
    }

    public function machinePricings()
    {
        return $this->hasMany(MachinePricing::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
