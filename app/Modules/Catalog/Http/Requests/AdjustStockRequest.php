<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $material instanceof Material
            && $this->user()?->can('adjust', $material) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'new_quantity' => ['nullable', 'decimal:0,2', 'regex:/^\d+(\.\d{1,2})?$/', 'min:0', 'max:99999999.99'],
            'delta' => ['nullable', 'decimal:0,2', 'regex:/^-?\d+(\.\d{1,2})?$/', 'min:-99999999.99', 'max:99999999.99'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasNewQuantity = $this->input('new_quantity') !== null;
            $hasDelta = $this->input('delta') !== null;

            if ($hasNewQuantity && $hasDelta) {
                $validator->errors()->add('new_quantity', 'Pilih salah satu antara jumlah baru atau selisih.');
                $validator->errors()->add('delta', 'Pilih salah satu antara jumlah baru atau selisih.');
            }

            if (! $hasNewQuantity && ! $hasDelta) {
                $validator->errors()->add('new_quantity', 'Isi jumlah baru atau selisih.');
            }

            if ($hasDelta && (float) $this->input('delta') === 0.0) {
                $validator->errors()->add('delta', 'Selisih stok tidak boleh nol.');
            }
        });
    }
}
