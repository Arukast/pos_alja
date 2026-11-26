<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Invoice;
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
        $branchId = $this->user()?->branch_id;

        return [
            'deadline' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid', 'distinct', 'exists:orders,id'],
            'items.*.pricing_id' => ['required', 'uuid', 'exists:pricing,id'],
            'items.*.file_name' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.width' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.length' => ['nullable', 'numeric', 'gt:0', 'max:999'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.selected_attributes' => ['nullable', 'array', 'max:3'],
            'items.*.selected_attributes.*' => ['uuid', 'distinct', 'exists:attributes,id'],
        ];
    }
}
