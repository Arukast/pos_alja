<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Models\WastedMaterial;
use Illuminate\Foundation\Http\FormRequest;

final class LogWasteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WastedMaterial::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'material_id' => ['required', 'uuid', 'exists:materials,id'],
            'designer_id' => ['nullable', 'uuid', 'exists:users,id'],
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'quantity' => ['required', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
