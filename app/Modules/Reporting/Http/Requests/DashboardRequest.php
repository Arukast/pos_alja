<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User || ! Gate::forUser($actor)->allows('viewDashboard')) {
            return false;
        }

        if ($actor->role !== 'owner') {
            if ($actor->branch_id === null) {
                return false;
            }

            if ($this->filled('branch_id')) {
                $requested = $this->query('branch_id');

                return is_string($requested) && $requested === $actor->branch_id;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'branch_id' => ['sometimes', 'string'],
        ];
    }

    public function period(): string
    {
        $period = $this->validated('period');

        return is_string($period) ? $period : 'daily';
    }

    public function resolvedBranchId(): string
    {
        $actor = $this->user();

        if (! $actor instanceof User) {
            return 'all';
        }

        if ($actor->role !== 'owner') {
            return (string) $actor->branch_id;
        }

        $branchId = $this->validated('branch_id');

        return is_string($branchId) && $branchId !== '' ? $branchId : 'all';
    }
}
