<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'role' => ['required', 'string', 'in:owner,manager,cashier,designer,production'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $actor = $this->user();

            if ($actor?->role === 'manager') {
                if (! in_array($this->input('role'), ['cashier', 'designer', 'production'], true)) {
                    $validator->errors()->add('role', 'Manajer hanya dapat membuat staf cabang.');
                }

                if ($this->input('branch_id') !== $actor->branch_id) {
                    $validator->errors()->add('branch_id', 'Manajer hanya dapat menetapkan cabangnya sendiri.');
                }
            }

            if ($this->input('role') !== 'owner' && ! $this->input('branch_id')) {
                $validator->errors()->add('branch_id', 'Cabang wajib diisi untuk peran selain pemilik.');
            }
        });
    }
}
