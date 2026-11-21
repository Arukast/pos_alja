<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDownPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && $this->user()?->can('pay', $invoice) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'in:Cash,Transfer,QRIS'],
        ];
    }
}
