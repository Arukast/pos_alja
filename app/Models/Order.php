<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'pricing_id',
        'operator_id',
        'file_name',
        'file_path',
        'preview_path',
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
        'is_manual_price',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function pricing()
    {
        return $this->belongsTo(Pricing::class, 'pricing_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
}
