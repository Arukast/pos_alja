<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

final class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createQuote', Invoice::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'material_id' => ['required', 'uuid', 'exists:materials,id'],
            'selected_attributes' => ['nullable', 'array'],
            'selected_attributes.*' => ['uuid', 'exists:attributes,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'width' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'length' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'customer_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
