<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'type',
        'dimensions',
        'cost',
        'price',
        'current_stock',
        'min_stock',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
