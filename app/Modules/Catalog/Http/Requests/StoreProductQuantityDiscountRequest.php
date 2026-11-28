<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\ProductQuantityDiscount;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductQuantityDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageProductQuantityDiscounts', ProductQuantityDiscount::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'min_qty' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
                Rule::unique('product_quantity_discounts', 'min_qty')
                    ->where(fn (Builder $query): Builder => $query->where('product_id', $this->input('product_id'))),
            ],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
