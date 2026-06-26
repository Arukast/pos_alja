<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'branch_id',
        'incurred_at',
        'amount',
        'description',
        'cashier_name',
        'type', // modal, laba
        'category',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
