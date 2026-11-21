<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deleteOwnProfile', $this->user()) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
