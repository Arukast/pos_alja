<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'pricing_matrix_id',
        'file_name',
        'notes',
        'area',
        'quantity',
        'unit_price',
        'subtotal',
        'cost',
        'profit',
        'ordered_at',
        'status',
        'pickup_status',
        'pickup_date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function pricingMatrix()
    {
        return $this->belongsTo(PricingMatrix::class);
    }
}
