<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachinePricing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'machine_pricing';

    protected $fillable = [
        'branch_id',
        'pricing_id',
        'machine_id',
        'price',
        'cost',
        'profit',
    ];

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
