<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttributeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type instanceof AttributeType
            && $this->user()?->can('update', $type) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->route('type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('attribute_types', 'name')
                    ->ignore($type instanceof AttributeType ? $type->id : null),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
