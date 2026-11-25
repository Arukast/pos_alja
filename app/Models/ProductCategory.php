<?php

declare(strict_types=1);

namespace App\Models;

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
}
