<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Events\OrderUpdated;
use App\Livewire\Forms\BranchForm;
use App\Models\Branch;
use App\Services\BranchService;
use Livewire\Component;
use Livewire\WithPagination;

class BranchManagement extends Component
{
    use WithPagination;

    public BranchForm $form;

    // Search and filters
    public string $search = '';

    public bool $showDeactivated = false;

    // Modals
    public bool $showFormModal = false;

    public ?string $editingBranchId = null;

    // Alerts
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    private BranchService $branchService;

    /**
     * Dependency injection via Livewire boot method.
     */
    public function boot(BranchService $branchService): void
    {
        $this->branchService = $branchService;
    }

    /**
     * Authorize access on mount.
     */
    public function mount(): void
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Unauthorized. Owner access only.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowDeactivated(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->form->resetFields();
        $this->editingBranchId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(string $branchId): void
    {
        $this->resetErrorBag();
        $branch = Branch::findOrFail($branchId);

        $this->editingBranchId = $branch->id;
        $this->form->setBranch($branch);
        $this->showFormModal = true;
    }

    public function saveBranch(): void
    {
        $payload = $this->form->validateAndGetPayload($this->editingBranchId);

        if ($this->editingBranchId) {
            $branch = Branch::findOrFail($this->editingBranchId);
            $this->branchService->updateBranch($branch, $payload, auth()->user());
            $this->successMessage = 'Branch updated successfully!';
        } else {
            $this->branchService->createBranch($payload, auth()->user());
            $this->successMessage = 'Branch created successfully!';
        }

        // Dispatch real-time update event
        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->showFormModal = false;
        $this->showSuccessAlert = true;
    }

    public function toggleBranchStatus(string $branchId): void
    {
        $branch = Branch::findOrFail($branchId);
        $this->branchService->toggleBranchStatus($branch, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'Branch status updated successfully!';
        $this->showSuccessAlert = true;
    }

    public function deleteBranch(string $branchId): void
    {
        $branch = Branch::findOrFail($branchId);
        $this->branchService->deleteBranch($branch, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'Branch deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $query = Branch::query();

        if ($this->showDeactivated) {
            $query->onlyTrashed();
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        $branchesList = $query->orderBy('name')->paginate(15);

        return view('livewire.admin.branch-management', compact('branchesList'));
    }
}
