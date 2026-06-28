<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
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

    // Public properties to persist state in Livewire v4 islands
    public array $sparklinePoints = [];

    public array $sparklineLabels = [];

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
        $grandTotal = (int) $paymentQuery->sum('amount');

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
        $profit = $grandTotal - $cost;

        // Build expenses query base
        $expenseQuery = Expense::query();
        if ($branchId !== 'all') {
            $expenseQuery->where('branch_id', $branchId);
        }
        $expenseQuery->whereBetween('incurred_at', [$startDate, $endDate]);
        $expensesTotal = (int) $expenseQuery->sum('amount');

        // Net margin rate
        $marginRate = $grandTotal > 0 ? ($profit / $grandTotal) * 100 : 0.0;

        // Sparkline coordinates: compute data based on active period filter
        $points = [];
        $labels = [];

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
                $points[] = $paySum - $costSum;
            }
            $labels = ['00:00-04:00', '04:00-08:00', '08:00-12:00', '12:00-16:00', '16:00-20:00', '20:00-24:00'];
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
                $points[] = $paySum - $costSum;
                $labels[] = $date->format('D');
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
                $points[] = $paySum - $costSum;

                if ($i === 0) {
                    $labels[] = 'This Week';
                } else {
                    $labels[] = $i.' wk ago';
                }
            }
        }

        $this->sparklinePoints = $points;
        $this->sparklineLabels = $labels;

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
            'grandTotal',
            'cost',
            'profit',
            'expensesTotal',
            'marginRate',
            'allBranches',
            'cashPayments',
            'transferPayments',
            'qrisPayments',
            'activityLogs',
            'selectedAuditLog'
        ));
    }
}
