<?php

namespace App\Livewire\Dashboards;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Owner extends Component
{
    use WithPagination;

    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public string $period = 'Monthly';

    public string $selectedBranchId = 'all';

    // Active tab
    public string $activeTab = 'finance';

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
            ->select('users.name', DB::raw('count(invoices.id) as orders_count'), DB::raw('sum(invoices.grand_total) as total_value'))
            ->whereBetween('invoices.created_date', [$startDate, $endDate]);
        if ($branchId !== 'all') {
            $leaderQuery->where('invoices.branch_id', $branchId);
        }
        $leaders = $leaderQuery->groupBy('users.id', 'users.name')
            ->orderBy('total_value', 'desc')
            ->get();

        // Sparkline coordinates: divide the selected range into 4 intervals
        $sparklinePoints = [];
        $carbonStart = Carbon::parse($startDate);
        $carbonEnd = Carbon::parse($endDate);

        $diffInDays = $carbonStart->diffInDays($carbonEnd);
        if ($diffInDays < 3) {
            // For Daily: query profit for the last 7 days to make a nice chart
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();

                $pQuery = Payment::whereDate('created_at', $date);
                $iQuery = Invoice::where('created_date', $date);
                if ($branchId !== 'all') {
                    $pQuery->where('branch_id', $branchId);
                    $iQuery->where('branch_id', $branchId);
                }
                $paySum = (int) $pQuery->sum('amount');
                $costSum = (int) $iQuery->sum('total_cost');
                $sparklinePoints[] = $paySum - $costSum;
            }
        } else {
            // Divide range into 4 sub-periods
            $step = max(1, (int) ($diffInDays / 3));
            for ($i = 0; $i < 4; $i++) {
                $subStart = $carbonStart->copy()->addDays($i * $step)->toDateString();
                $subEnd = $carbonStart->copy()->addDays(($i + 1) * $step - 1)->toDateString();
                if ($i === 3) {
                    $subEnd = $endDate;
                }
                $pQuery = Payment::whereDate('created_at', '>=', $subStart)
                    ->whereDate('created_at', '<=', $subEnd);
                $iQuery = Invoice::whereBetween('created_date', [$subStart, $subEnd]);
                if ($branchId !== 'all') {
                    $pQuery->where('branch_id', $branchId);
                    $iQuery->where('branch_id', $branchId);
                }
                $paySum = (int) $pQuery->sum('amount');
                $costSum = (int) $iQuery->sum('total_cost');
                $sparklinePoints[] = $paySum - $costSum;
            }
        }

        // Map points to SVG coordinates
        $svgPath = 'M 0 80 L 300 80'; // Default flat line
        $svgPoints = [];

        $maxVal = max($sparklinePoints);
        $minVal = min($sparklinePoints);
        $valRange = $maxVal - $minVal;

        if ($maxVal > 0 || $minVal < 0) {
            $svgPoints = [];
            $count = count($sparklinePoints);
            for ($idx = 0; $idx < $count; $idx++) {
                // Guard: if only 1 point, avoid divide-by-zero ($count - 1 = 0)
                $x = $count > 1 ? (int) (($idx / ($count - 1)) * 300) : 150;
                $val = $sparklinePoints[$idx];
                if ($valRange > 0) {
                    $y = 80 - (int) ((($val - $minVal) / $valRange) * 60);
                } else {
                    $y = 50;
                }
                $svgPoints[] = "$x $y";
            }
            if ($svgPoints) {
                $svgPath = 'M '.implode(' L ', $svgPoints);
            }
        }

        $allBranches = Branch::all();

        // Paginated Activity Logs for Audit Trail
        $activityLogs = $this->activeTab === 'audit'
            ? ActivityLog::query()
                ->when($branchId !== 'all', function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15)
            : new LengthAwarePaginator([], 0, 15);

        return view('livewire.dashboards.owner', compact(
            'revenue',
            'cost',
            'profit',
            'expensesTotal',
            'marginRate',
            'leaders',
            'svgPath',
            'sparklinePoints',
            'svgPoints',
            'allBranches',
            'cashPayments',
            'transferPayments',
            'qrisPayments',
            'activityLogs'
        ));
    }
}
