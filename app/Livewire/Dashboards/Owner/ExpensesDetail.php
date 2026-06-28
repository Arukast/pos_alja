<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards\Owner;

use App\Livewire\Forms\DateRangeForm;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ExpensesDetail extends Component
{
    public DateRangeForm $form;

    public string $selectedBranchId = 'all';

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
        'branchSelected' => 'updateBranch',
        'periodSelected' => 'updatePeriodDates',
    ];

    public function mount(string $selectedBranchId, string $period): void
    {
        $this->selectedBranchId = $selectedBranchId;
        $this->updatePeriodDates($period);
    }

    public function updateBranch(string $branchId): void
    {
        $this->selectedBranchId = $branchId;
    }

    public function updatePeriodDates(string $period): void
    {
        if ($period === 'Daily') {
            $this->form->startDate = now()->toDateString();
            $this->form->endDate = now()->toDateString();
        } elseif ($period === 'Weekly') {
            $this->form->startDate = now()->startOfWeek()->toDateString();
            $this->form->endDate = now()->endOfWeek()->toDateString();
        } elseif ($period === 'Monthly') {
            $this->form->startDate = now()->startOfMonth()->toDateString();
            $this->form->endDate = now()->endOfMonth()->toDateString();
        }
    }

    public function applyCustomRange(): void
    {
        $this->validate();
    }

    /**
     * @return View
     */
    public function render(FinanceService $financeService)
    {
        $data = $financeService->getExpensesBreakdown(
            $this->selectedBranchId,
            $this->form->startDate,
            $this->form->endDate
        );

        return view('livewire.dashboards.owner.expenses-detail', $data);
    }
}
