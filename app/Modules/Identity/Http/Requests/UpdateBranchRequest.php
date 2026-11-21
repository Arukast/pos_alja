<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('branch')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(Branch::class, 'code')->ignore($branch instanceof Branch ? $branch->id : null),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
