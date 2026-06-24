<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'incurred_at',
        'amount',
        'description',
        'cashier_name',
        'type', // modal, laba
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
