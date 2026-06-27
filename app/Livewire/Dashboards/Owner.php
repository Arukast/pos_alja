<?php

namespace App\Livewire\Dashboards;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WastedMaterial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Owner extends Component
{
    use WithPagination;

    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public string $period = 'Daily';

    public string $selectedBranchId = 'all';

    // Active tab
    #[Url(as: 'tab')]
    public string $activeTab = 'finance';

    // Audit Trail states
    public string $auditSearch = '';

    public string $auditSortField = 'created_at';

    public string $auditSortDirection = 'desc';

    public bool $showAuditDetailModal = false;

    public ?string $selectedAuditLogId = null;

    public function openAuditDetail(string $logId): void
    {
        $this->selectedAuditLogId = $logId;
        $this->showAuditDetailModal = true;
    }

    public function closeAuditDetail(): void
    {
        $this->selectedAuditLogId = null;
        $this->showAuditDetailModal = false;
    }

    public function updatingAuditSearch(): void
    {
        $this->resetPage();
    }

    public function sortByAudit(string $field): void
    {
        if ($this->auditSortField === $field) {
            $this->auditSortDirection = $this->auditSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->auditSortField = $field;
            $this->auditSortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination page when switching tabs
        $this->resetErrorBag();
    }

    public function mount(): void
    {
        // Owner dashboard initializations
    }

    public function render()
    {
        $branchId = $this->selectedBranchId;
        $period = $this->period;

        // Date range calculation
        if ($period === 'Daily') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($period === 'Weekly') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->toDateString();
        } else { // Monthly
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // Build payment query base for Gross Revenue (Omset)
        $paymentQuery = Payment::query();
        if ($branchId !== 'all') {
            $paymentQuery->where('branch_id', $branchId);
        }
        $paymentQuery->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        // Sum values
        $revenue = (int) $paymentQuery->sum('amount');

        // Cash distribution breakdown
        $cashPayments = (int) (clone $paymentQuery)->where('payment_method', 'Cash')->sum('amount');
        $transferPayments = (int) (clone $paymentQuery)->where('payment_method', 'Transfer')->sum('amount');
        $qrisPayments = (int) (clone $paymentQuery)->where('payment_method', 'QRIS')->sum('amount');

        // Build invoice query base for HPP (Base Cost)
        $invoiceQuery = Invoice::query();
        if ($branchId !== 'all') {
            $invoiceQuery->where('branch_id', $branchId);
        }
        $invoiceQuery->whereBetween('created_date', [$startDate, $endDate]);

        $cost = (int) $invoiceQuery->sum('total_cost');

        // Net profit based on cash collected minus base cost
        $profit = $revenue - $cost;

        // Build expenses query base
        $expenseQuery = Expense::query();
        if ($branchId !== 'all') {
            $expenseQuery->where('branch_id', $branchId);
        }
        $expenseQuery->whereBetween('incurred_at', [$startDate, $endDate]);
        $expensesTotal = (int) $expenseQuery->sum('amount');

        // Net margin rate
        $marginRate = $revenue > 0 ? ($profit / $revenue) * 100 : 0.0;

        // Leaderboard: group invoices by designer_id (a UUID foreign key) in the range
        $leaderQuery = Invoice::query()
            ->join('users', 'invoices.designer_id', '=', 'users.id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'users.id as designer_id',
                'users.name',
                'branches.name as branch_name',
                DB::raw('count(invoices.id) as orders_count'),
                DB::raw('sum(invoices.grand_total) as total_value')
            )
            ->whereBetween('invoices.created_date', [$startDate, $endDate]);
        if ($branchId !== 'all') {
            $leaderQuery->where('invoices.branch_id', $branchId);
        }
        $leaders = $leaderQuery->groupBy('users.id', 'users.name', 'branches.name')
            ->orderBy('total_value', 'desc')
            ->get();

        // Get wasted material logs count for each designer
        $wasteQuery = WastedMaterial::query()
            ->select('designer_id', DB::raw('count(*) as waste_count'))
            ->whereBetween(DB::raw('DATE(logged_at)'), [$startDate, $endDate]);
        if ($branchId !== 'all') {
            $wasteQuery->where('branch_id', $branchId);
        }
        $wastes = $wasteQuery->groupBy('designer_id')
            ->pluck('waste_count', 'designer_id')
            ->toArray();

        // Map wastes, average order value (AOV) into the leaderboard collection
        $leaders = $leaders->map(function ($leader) use ($wastes) {
            $designerId = (string) $leader->getAttribute('designer_id');
            $ordersCount = (int) $leader->getAttribute('orders_count');
            $totalValue = (float) $leader->getAttribute('total_value');

            $leaderObj = new \stdClass;
            $leaderObj->designer_id = $designerId;
            $leaderObj->name = (string) $leader->getAttribute('name');
            $leaderObj->branch_name = $leader->getAttribute('branch_name') ? (string) $leader->getAttribute('branch_name') : null;
            $leaderObj->orders_count = $ordersCount;
            $leaderObj->total_value = $totalValue;
            $leaderObj->waste_count = (int) ($wastes[$designerId] ?? 0);
            $leaderObj->aov = $ordersCount > 0 ? (float) ($totalValue / $ordersCount) : 0.0;

            return $leaderObj;
        });

        // Compute performance highlight cards
        $topRevenue = $leaders->first();
        $orderChampion = $leaders->sortByDesc('orders_count')->first();
        $topAov = $leaders->sortByDesc('aov')->first();
        $accuracyLeader = $leaders->filter(fn ($l) => $l->orders_count > 0)
            ->sortBy([
                ['waste_count', 'asc'],
                ['orders_count', 'desc'],
            ])->first();

        // Sparkline coordinates: compute data based on active period filter
        $sparklinePoints = [];
        $sparklineLabels = [];

        if ($period === 'Daily') {
            // Daily: 24-hour day split into 6 equal intervals (each 4 hours: 0-4, 4-8, 8-12, 12-16, 16-20, 20-24)
            for ($i = 0; $i < 6; $i++) {
                $startHour = $i * 4;
                $endHour = ($i + 1) * 4;

                $startDateTime = now()->startOfDay()->addHours($startHour);
                // Adjust end interval to avoid overlaps at the boundary (e.g. 03:59:59)
                $endDateTime = now()->startOfDay()->addHours($endHour)->subSecond();

                $pQuery = Payment::whereBetween('created_at', [$startDateTime, $endDateTime]);
                $iQuery = Invoice::whereBetween('created_date', [$startDateTime, $endDateTime]);

                if ($branchId !== 'all') {
                    $pQuery->where('branch_id', $branchId);
                    $iQuery->where('branch_id', $branchId);
                }

                $paySum = (int) $pQuery->sum('amount');
                $costSum = (int) $iQuery->sum('total_cost');
                $sparklinePoints[] = $paySum - $costSum;
            }
            $sparklineLabels = ['00:00-04:00', '04:00-08:00', '08:00-12:00', '12:00-16:00', '16:00-20:00', '20:00-24:00'];
        } elseif ($period === 'Weekly') {
            // Weekly: last 7 days leading up to today
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $startDateTime = $date->copy()->startOfDay();
                $endDateTime = $date->copy()->endOfDay();

                $pQuery = Payment::whereBetween('created_at', [$startDateTime, $endDateTime]);
                $iQuery = Invoice::whereBetween('created_date', [$startDateTime, $endDateTime]);

                if ($branchId !== 'all') {
                    $pQuery->where('branch_id', $branchId);
                    $iQuery->where('branch_id', $branchId);
                }

                $paySum = (int) $pQuery->sum('amount');
                $costSum = (int) $iQuery->sum('total_cost');
                $sparklinePoints[] = $paySum - $costSum;
                $sparklineLabels[] = $date->format('D');
            }
        } else {
            // Monthly: last 4 weeks leading up to today
            for ($i = 3; $i >= 0; $i--) {
                $weekStart = now()->subWeeks($i)->startOfWeek()->startOfDay();
                $weekEnd = now()->subWeeks($i)->endOfWeek()->endOfDay();

                $pQuery = Payment::whereBetween('created_at', [$weekStart, $weekEnd]);
                $iQuery = Invoice::whereBetween('created_date', [$weekStart, $weekEnd]);

                if ($branchId !== 'all') {
                    $pQuery->where('branch_id', $branchId);
                    $iQuery->where('branch_id', $branchId);
                }

                $paySum = (int) $pQuery->sum('amount');
                $costSum = (int) $iQuery->sum('total_cost');
                $sparklinePoints[] = $paySum - $costSum;

                if ($i === 0) {
                    $sparklineLabels[] = 'This Week';
                } else {
                    $sparklineLabels[] = $i.' wk ago';
                }
            }
        }

        $allBranches = Branch::all();

        // Paginated Activity Logs for Audit Trail
        $activityLogs = $this->activeTab === 'audit'
            ? ActivityLog::query()
                ->when($branchId !== 'all', function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })
                ->when(! empty($this->auditSearch), function ($q) {
                    $search = '%'.$this->auditSearch.'%';

                    return $q->where(function ($sub) use ($search) {
                        $sub->where('username', 'like', $search)
                            ->orWhere('role', 'like', $search)
                            ->orWhere('action', 'like', $search)
                            ->orWhere('description', 'like', $search);
                    });
                })
                ->orderBy($this->auditSortField, $this->auditSortDirection)
                ->paginate(10)
            : new LengthAwarePaginator([], 0, 10);

        $selectedAuditLog = $this->selectedAuditLogId ? ActivityLog::find($this->selectedAuditLogId) : null;

        return view('livewire.dashboards.owner', compact(
            'revenue',
            'cost',
            'profit',
            'expensesTotal',
            'marginRate',
            'leaders',
            'sparklinePoints',
            'allBranches',
            'cashPayments',
            'transferPayments',
            'qrisPayments',
            'activityLogs',
            'topRevenue',
            'orderChampion',
            'topAov',
            'accuracyLeader',
            'selectedAuditLog',
            'sparklineLabels'
        ));
    }
}
