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
        'description',
        'is_active',
        'calculation_archetype',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

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
     * Stable audit snapshot of the API-visible category fields.
     *
     * @return array{name: string, description: string|null, is_active: bool}
     */
    public function auditSnapshot(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
