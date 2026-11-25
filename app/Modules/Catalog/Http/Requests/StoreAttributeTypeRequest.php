<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\AttributeType;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAttributeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAttributeTypes', AttributeType::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:attribute_types,name'],
            'calculation_archetype' => [
                'required',
                'string',
                'in:per_piece,surface_area,linear_dimension,tiered_volume',
            ],
        ];
    }
}
