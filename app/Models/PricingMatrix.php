<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingMatrix extends Model
{
    use HasFactory;

    protected $table = 'pricing_matrices';

    protected $fillable = [
        'branch_id',
        'product_id',
        'material_id',
        'machine_id',
        'finishing_id',
        'cut_id',
        'dimensions',
        'display_id',
        'price',
        'cost',
        'profit',
        'min_qty',
        'sides',
        'finishing_sides',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
