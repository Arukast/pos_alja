<?php

declare(strict_types=1);

namespace App\Modules\Audit\Queries;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class AuditLogList
{
    /**
     * @param  array{search?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $allowedSorts = ['created_at', 'username', 'role', 'action'];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true)
            ? $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return ActivityLog::query()
            ->with(['branch', 'user'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();
    }
}
