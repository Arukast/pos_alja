<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'type',
        'dimensions',
        'cost',
        'price',
        'initial_stock',
        'added_stock',
        'current_stock',
        'sold_stock',
        'wasted_stock',
        'remaining_stock',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
