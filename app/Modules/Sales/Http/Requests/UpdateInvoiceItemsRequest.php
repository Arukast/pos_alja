<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/** Validates the complete replacement item list for a pre-production invoice. */
final class UpdateInvoiceItemsRequest extends FormRequest
{
    /** Authorizes edits through the invoice record policy. */
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && $this->user()?->can('update', $invoice) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'deadline' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid', 'distinct', 'exists:orders,id'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.material_id' => ['nullable', 'uuid', 'exists:materials,id'],
            'items.*.file_name' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.width' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.length' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.selected_attributes' => ['nullable', 'array'],
            'items.*.selected_attributes.*' => ['uuid', 'distinct', 'exists:attributes,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                if (! is_array($item) || ! is_string($item['product_id'] ?? null)) {
                    continue;
                }

                $product = Product::query()->find($item['product_id']);

                if ($product === null) {
                    continue;
                }

                $materialId = $item['material_id'] ?? null;
                $hasMaterial = is_string($materialId) && $materialId !== '';

                if ($product->requires_material && ! $hasMaterial) {
                    $validator->errors()->add("items.{$index}.material_id", 'Bahan wajib dipilih untuk produk ini.');
                }

                if (! $product->requires_material && $hasMaterial) {
                    $validator->errors()->add("items.{$index}.material_id", 'Produk ini tidak menggunakan bahan.');
                }
            }
        });
    }
}
