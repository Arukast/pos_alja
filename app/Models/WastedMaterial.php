<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastedMaterial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'wasted_materials';

    protected $fillable = [
        'branch_id',
        'logged_at',
        'material_id',
        'machine_id',
        'order_id',
        'designer_id',
        'operator_id',
        'quantity',
        'dimensions',
        'notes',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
