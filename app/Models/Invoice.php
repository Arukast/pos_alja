<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'created_date',
        'payment_date',
        'dp_date',
        'customer_id',
        'status',
        'discount',
        'dp_amount',
        'accounts_receivable',
        'dp_paid',
        'settlement_amount',
        'grand_total',
        'payment_method',
        'payment_amount',
        'change_amount',
        'total_cost',
        'total_profit',
        'customer_service',
        'deadline',
        'pickup_status',
        'pickup_date',
        'source',
        'cashier_name',
        'cashier_dp_name',
        'payment_dp_method',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
