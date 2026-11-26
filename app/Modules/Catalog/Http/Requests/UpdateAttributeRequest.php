<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAttributes', Attribute::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:finishing,display'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
