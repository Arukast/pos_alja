<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $branch_id
 * @property string $name
 * @property string $type
 * @property string|float $price
 * @property string|float $cost
 * @property bool $is_active
 * @property-read Branch|null $branch
 * @property-read AttributeType|null $typeInfo
 */
class Attribute extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'type', // finishing, cut, display
        'price',
        'cost',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<AttributeType, $this>
     */
    public function typeInfo(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class, 'type', 'slug');
    }
}
