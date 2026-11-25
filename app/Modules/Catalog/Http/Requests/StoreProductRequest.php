<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
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
            'pricing_type' => ['required', 'string', 'in:unit,area'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
