<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageCategories', ProductCategory::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'discount_tiers' => ['sometimes', 'array'],
            'discount_tiers.*.min_qty' => ['required', 'integer', 'min:1', 'distinct'],
            'discount_tiers.*.discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
