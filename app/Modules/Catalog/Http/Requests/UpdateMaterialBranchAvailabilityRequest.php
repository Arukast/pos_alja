<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMaterialBranchAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $material instanceof Material
            && $this->user()?->can('setBranchAvailability', $material) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                Rule::requiredIf(fn (): bool => $this->user()?->role === 'owner'),
                'nullable',
                'uuid',
                'exists:branches,id',
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
