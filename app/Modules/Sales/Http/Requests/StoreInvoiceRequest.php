<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Invoice::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'institution' => ['nullable', 'string', Rule::in(Customer::INSTITUTIONS)],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'deadline' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'in:Offline,Online'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pricing_id' => ['required', 'uuid', 'exists:pricing,id'],
            'items.*.file_name' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.width' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.length' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.selected_attributes' => ['nullable', 'array'],
            'items.*.selected_attributes.*' => ['uuid', 'exists:attributes,id'],
            'items.*.is_manual_price' => ['sometimes', 'boolean'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }
}
