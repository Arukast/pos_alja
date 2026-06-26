<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingMatrix extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        'customer_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class)->withDefault(['name' => '-']);
    }

    public function finishing()
    {
        return $this->belongsTo(Attribute::class, 'finishing_id')->where('type', 'finishing')->withDefault(['name' => '-']);
    }

    public function cut()
    {
        return $this->belongsTo(Attribute::class, 'cut_id')->where('type', 'cut')->withDefault(['name' => '-']);
    }

    public function display()
    {
        return $this->belongsTo(Attribute::class, 'display_id')->where('type', 'display')->withDefault(['name' => '-']);
    }
}
