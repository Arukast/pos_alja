<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Requests;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;

final class PinBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $backup = $this->route('backup');

        return $backup instanceof Backup
            && $this->user()?->can('update', $backup) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pinned' => ['required', 'boolean'],
        ];
    }
}
