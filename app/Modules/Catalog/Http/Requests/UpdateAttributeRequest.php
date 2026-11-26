<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attribute = $this->route('attribute');

        return $attribute instanceof Attribute
            && $this->user()?->can('updateAttribute', $attribute) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:finishing,display'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
