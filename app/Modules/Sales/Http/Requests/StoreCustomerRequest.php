<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
