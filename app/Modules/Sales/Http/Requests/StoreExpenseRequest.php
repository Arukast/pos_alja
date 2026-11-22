<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

final class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', 'string', 'in:laba,modal'],
            'incurred_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
