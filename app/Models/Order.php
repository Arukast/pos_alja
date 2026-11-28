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
        'product_id',
        'material_id',
        'customer_type',
        'reseller_discount_percent',
        'quantity_discount_percent',
        'discount_amount',
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
        'reseller_discount_percent' => 'decimal:2',
        'quantity_discount_percent' => 'decimal:2',
        'customer_type' => 'string',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
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
