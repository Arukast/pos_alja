<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributePricing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'attribute_pricing';

    protected $fillable = [
        'branch_id',
        'pricing_id',
        'attribute_id',
        'price',
        'cost',
        'profit',
    ];

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
