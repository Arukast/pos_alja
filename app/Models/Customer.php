<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var list<string> */
    public const INSTITUTIONS = [
        'Masyarakat/Retail',
        'Sekolah',
        'Universitas',
        'Pemerintahan',
        'Bisnis/Perusahaan',
        'Organisasi/Yayasan',
    ];

    protected $fillable = [
        'name',
        'phone',
        'address',
        'email',
        'type',
        'institution',
        'institutions',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'institutions' => 'array',
    ];

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
