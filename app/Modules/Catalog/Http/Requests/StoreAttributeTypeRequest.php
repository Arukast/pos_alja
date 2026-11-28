<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAttributeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AttributeType::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('attribute_types', 'name')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
