<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Product;
use App\Models\ProductQuantityDiscount;
use App\Support\MaterialUnit;
use Closure;
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
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_material' => ['sometimes', 'boolean'],
            'uses_attributes' => ['sometimes', 'boolean'],
            'material_ids' => ['sometimes', 'array'],
            'material_ids.*' => ['uuid', 'distinct', Rule::exists('materials', 'id')->whereNull('deleted_at')],
            'attribute_type_ids' => ['sometimes', 'array'],
            'attribute_type_ids.*' => ['uuid', 'distinct', 'exists:attribute_types,id'],
            'attribute_ids' => ['sometimes', 'array'],
            'attribute_ids.*' => ['uuid', 'distinct', Rule::exists('attributes', 'id')->whereNull('deleted_at')],
            'tiers' => ['sometimes', 'array'],
            'tiers.*' => ['array'],
            'tiers.*.min_qty' => ['required', 'integer', 'min:1', 'max:100000', 'distinct'],
            'tiers.*.discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tiers.*.id' => [
                'sometimes',
                'uuid',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $product = $this->route('product');
                    $owned = $product instanceof Product && ProductQuantityDiscount::query()
                        ->whereKey($value)
                        ->where('product_id', $product->id)
                        ->exists();

                    if (! $owned) {
                        $fail('Tier tidak ditemukan pada produk ini.');
                    }
                },
            ],
        ];
    }
}
