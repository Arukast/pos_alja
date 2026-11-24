<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Purchase::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'purchased_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.material_id' => [
                'required',
                'uuid',
                Rule::exists('materials', 'id')->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'decimal:0,2', 'regex:/^\d+(\.\d{1,2})?$/', 'min:0.01', 'max:999999.99'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'regex:/^\d+(\.\d{1,2})?$/', 'min:0', 'max:9999999.99'],
        ];
    }
}
