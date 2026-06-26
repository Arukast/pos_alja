<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_ledgers';

    protected $fillable = [
        'branch_id',
        'material_id',
        'logged_at',
        'initial_stock',
        'added_stock',
        'used_stock',
        'wasted_stock',
        'remaining_stock',
        'notes',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
