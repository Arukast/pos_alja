<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'selling_unit',
        'product_category_id',
        'price',
        'cost',
        'is_active',
        'requires_material',
        'uses_attributes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'requires_material' => 'boolean',
        'uses_attributes' => 'boolean',
    ];

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return BelongsToMany<Material, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_product', 'product_id', 'material_id')
            ->withTimestamps()
            ->orderBy('materials.name');
    }

    /**
     * @return BelongsToMany<AttributeType, $this>
     */
    public function attributeTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeType::class,
            'attribute_type_product',
            'product_id',
            'attribute_type_id',
        )->withTimestamps()->orderBy('attribute_types.name');
    }

    /**
     * @return BelongsToMany<Attribute, $this>
     */
    public function compatibleAttributes(): BelongsToMany
    {
        return $this->belongsToMany(
            Attribute::class,
            'attribute_product',
            'product_id',
            'attribute_id',
        )->withTimestamps()->orderBy('attributes.name');
    }

    /**
     * @return HasMany<ProductQuantityDiscount, $this>
     */
    public function quantityDiscounts(): HasMany
    {
        return $this->hasMany(ProductQuantityDiscount::class)->orderBy('min_qty');
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'requires_material' => (bool) $this->requires_material,
            'uses_attributes' => (bool) $this->uses_attributes,
            'material_ids' => $this->materials()->pluck('materials.id')->values()->all(),
            'attribute_type_ids' => $this->attributeTypes()->pluck('attribute_type_product.attribute_type_id')->values()->all(),
            'attribute_ids' => $this->compatibleAttributes()->pluck('attribute_product.attribute_id')->values()->all(),
            'tiers' => $this->quantityDiscounts()->orderBy('min_qty')->get()->map(
                static fn (ProductQuantityDiscount $tier): array => [
                    'id' => $tier->id,
                    'min_qty' => (int) $tier->min_qty,
                    'discount_percent' => Decimal::money($tier->discount_percent),
                ]
            )->values()->all(),
        ];
    }
}
