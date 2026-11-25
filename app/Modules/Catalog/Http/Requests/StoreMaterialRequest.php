<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Material;
use App\Support\MaterialUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Material::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', Rule::in(MaterialUnit::VALUES)],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'initial_stock' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
