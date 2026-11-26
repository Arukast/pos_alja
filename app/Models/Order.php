<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'width',
        'length',
        'quantity',
        'selected_attributes',
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

    protected $casts = [
        'selected_attributes' => 'array',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Pricing, $this>
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(Pricing::class, 'pricing_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * @return HasMany<OrderStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    /**
     * @return HasMany<WastedMaterial, $this>
     */
    public function wastedMaterials(): HasMany
    {
        return $this->hasMany(WastedMaterial::class);
    }
}
