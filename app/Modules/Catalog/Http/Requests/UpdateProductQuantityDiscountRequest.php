<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\ProductQuantityDiscount;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductQuantityDiscountRequest extends FormRequest
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
        $tier = $this->route('tier');

        return [
            'min_qty' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
                Rule::unique('product_quantity_discounts', 'min_qty')
                    ->where(fn (Builder $query): Builder => $query->where(
                        'product_id',
                        $tier instanceof ProductQuantityDiscount ? $tier->product_id : null
                    ))
                    ->ignore($tier instanceof ProductQuantityDiscount ? $tier->id : null),
            ],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
