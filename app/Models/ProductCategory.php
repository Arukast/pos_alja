<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'calculation_archetype',
        'allowed_attribute_types',
    ];

    protected $casts = [
        'allowed_attribute_types' => 'array',
    ];

    /**
     * Determine if an attribute (or type string) is permitted within this category.
     */
    public function isAttributeAllowed(string|Attribute $attribute): bool
    {
        $type = $attribute instanceof Attribute ? $attribute->type : $attribute;

        if (empty($this->allowed_attribute_types)) {
            return true;
        }

        return in_array($type, $this->allowed_attribute_types, true);
    }

    /**
     * Get the products for this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    /**
     * Get the materials for this category.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'product_category_id');
    }

    /**
     * @return HasMany<ProductCategoryDiscount, $this>
     */
    public function discountTiers(): HasMany
    {
        return $this->hasMany(ProductCategoryDiscount::class, 'product_category_id')
            ->orderBy('min_qty');
    }

    /**
     * Stable audit snapshot; reads the tiers fresh so update/delete see current rows.
     *
     * @return array{
     *     name: string,
     *     calculation_archetype: string,
     *     allowed_attribute_types: array<int, string>,
     *     discount_tiers: array<int, array{min_qty: int, discount_percent: string}>
     * }
     */
    public function auditSnapshot(): array
    {
        return [
            'name' => $this->name,
            'calculation_archetype' => $this->calculation_archetype,
            'allowed_attribute_types' => $this->allowed_attribute_types ?? [],
            'discount_tiers' => $this->discountTiers()
                ->get()
                ->map(static fn (ProductCategoryDiscount $tier): array => [
                    'min_qty' => (int) $tier->min_qty,
                    'discount_percent' => Decimal::money($tier->discount_percent),
                ])
                ->values()
                ->all(),
        ];
    }
}
