<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Product;
use App\Support\MaterialUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageProducts', Product::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'selling_unit' => ['sometimes', 'required', 'string', Rule::in(MaterialUnit::VALUES)],
            'is_active' => ['sometimes', 'boolean'],
            'requires_material' => ['sometimes', 'boolean'],
            'uses_attributes' => ['sometimes', 'boolean'],
            'material_ids' => ['sometimes', 'array'],
            'material_ids.*' => ['uuid', 'distinct', Rule::exists('materials', 'id')->whereNull('deleted_at')],
            'attribute_type_ids' => ['sometimes', 'array'],
            'attribute_type_ids.*' => ['uuid', 'distinct', 'exists:attribute_types,id'],
            'attribute_ids' => ['sometimes', 'array'],
            'attribute_ids.*' => ['uuid', 'distinct', Rule::exists('attributes', 'id')->whereNull('deleted_at')],
        ];
    }
}
