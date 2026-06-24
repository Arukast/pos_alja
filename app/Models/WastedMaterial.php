<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastedMaterial extends Model
{
    use HasFactory;

    protected $table = 'wasted_materials';

    protected $fillable = [
        'branch_id',
        'logged_at',
        'material_id',
        'machine_id',
        'quantity',
        'dimensions',
        'designer_name',
        'operator_name',
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
}
