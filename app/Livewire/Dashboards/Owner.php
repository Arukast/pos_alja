<?php

namespace App\Livewire\Dashboards;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Owner extends Component
{
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

        // Build invoice query base
        $invoiceQuery = Invoice::query();
        if ($branchId !== 'all') {
            $invoiceQuery->where('branch_id', (int) $branchId);
        }
        $invoiceQuery->whereBetween('created_date', [$startDate, $endDate]);

        // Sum values
        $revenue = (int) $invoiceQuery->sum('grand_total');
        $cost = (int) $invoiceQuery->sum('total_cost');
        $profit = (int) $invoiceQuery->sum('total_profit');

        // Build expenses query base
        $expenseQuery = Expense::query();
        if ($branchId !== 'all') {
            $expenseQuery->where('branch_id', (int) $branchId);
        }
        $expenseQuery->whereBetween('incurred_at', [$startDate, $endDate]);
        $expensesTotal = (int) $expenseQuery->sum('amount');

        // Net margin rate
        $marginRate = $revenue > 0 ? ($profit / $revenue) * 100 : 0.0;

        // Leaderboard: group invoices by customer_service (CS designer) in the range
        $leaderQuery = Invoice::query()
            ->select('customer_service as name', DB::raw('count(*) as orders_count'), DB::raw('sum(grand_total) as total_value'))
            ->whereNotNull('customer_service')
            ->whereBetween('created_date', [$startDate, $endDate]);
        if ($branchId !== 'all') {
            $leaderQuery->where('branch_id', (int) $branchId);
        }
        $leaders = $leaderQuery->groupBy('customer_service')
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
                $q = Invoice::where('created_date', $date);
                if ($branchId !== 'all') {
                    $q->where('branch_id', (int) $branchId);
                }
                $sparklinePoints[] = (int) $q->sum('total_profit');
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
                $q = Invoice::whereBetween('created_date', [$subStart, $subEnd]);
                if ($branchId !== 'all') {
                    $q->where('branch_id', (int) $branchId);
                }
                $sparklinePoints[] = (int) $q->sum('total_profit');
            }
        }

        // Map points to SVG coordinates
        $svgPath = 'M 0 80 L 300 80'; // Default flat line
        $svgPoints = [];
        $maxVal = max($sparklinePoints);
        $minVal = min($sparklinePoints);
        $valRange = $maxVal - $minVal;

        if ($maxVal > 0) {
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
                $svgPath = 'M ' . implode(' L ', $svgPoints);
            }
        }

        $allBranches = Branch::all();

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
            'allBranches'
        ));
    }
}
