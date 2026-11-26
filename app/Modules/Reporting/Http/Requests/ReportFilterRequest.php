<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        if (! $actor instanceof User || ! Gate::forUser($actor)->allows('viewFinancialReports')) {
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
        $period = $this->query('period');
        $usesPreset = is_string($period) && in_array($period, ['daily', 'weekly', 'monthly'], true);

        return [
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'branch_id' => ['nullable', 'string'],
            'start_date' => [$usesPreset ? 'nullable' : 'required', 'date'],
            'end_date' => [$usesPreset ? 'nullable' : 'required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function period(): string
    {
        $period = $this->validated('period');

        return is_string($period) ? $period : 'custom';
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

    /**
     * @return array{0: string, 1: string}
     */
    public function dateRange(): array
    {
        $startDate = $this->validated('start_date');
        $endDate = $this->validated('end_date');

        if (is_string($startDate) && $startDate !== '' && is_string($endDate) && $endDate !== '') {
            return [$startDate, $endDate];
        }

        return match ($this->period()) {
            'weekly' => [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ],
            'monthly' => [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],
            default => [
                now()->toDateString(),
                now()->toDateString(),
            ],
        };
    }
}
