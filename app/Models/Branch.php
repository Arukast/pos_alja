<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string|null $code
 * @property string|null $address
 * @property string|null $phone
 * @property bool $is_active
 * @property string|float $reseller_discount_percent
 * @property array<string, mixed>|null $settings
 */
class Branch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
        'reseller_discount_percent',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reseller_discount_percent' => 'decimal:2',
        'settings' => 'array',
    ];

    /**
     * Get the reseller discount percentage for this branch.
     */
    public function getResellerDiscountPercent(): float
    {
        $columnValue = (float) $this->reseller_discount_percent;

        if ($columnValue > 0.0) {
            return $columnValue;
        }

        return (float) ($this->settings['reseller_discount_percent'] ?? 0.0);
    }
}
