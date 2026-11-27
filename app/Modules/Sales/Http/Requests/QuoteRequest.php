<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createQuote', Invoice::class) === true;
    }

    protected function prepareForValidation(): void
    {
        $selected = $this->input('selected_attributes');

        if (is_array($selected)) {
            $this->merge([
                'selected_attributes' => array_map(
                    static fn (mixed $id): mixed => is_string($id) ? strtolower($id) : $id,
                    $selected
                ),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'material_id' => ['nullable', 'uuid', 'exists:materials,id'],
            'selected_attributes' => ['nullable', 'array'],
            'selected_attributes.*' => ['uuid', 'exists:attributes,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'width' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'length' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'customer_type' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $product = Product::query()->find($this->input('product_id'));

            if ($product === null) {
                return;
            }

            $material = $this->input('material_id');
            $hasMaterial = is_string($material) && $material !== '';

            if ($product->requires_material && ! $hasMaterial) {
                $validator->errors()->add('material_id', 'Bahan wajib dipilih untuk produk ini.');
            }

            if (! $product->requires_material && $hasMaterial) {
                $validator->errors()->add('material_id', 'Produk ini tidak menggunakan bahan.');
            }
        });
    }
}
