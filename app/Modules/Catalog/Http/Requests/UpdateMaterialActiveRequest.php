<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMaterialActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateActive', Material::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
