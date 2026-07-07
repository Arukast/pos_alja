<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Events\OrderUpdated;
use App\Livewire\Forms\MachineForm;
use App\Models\Branch;
use App\Models\Machine;
use App\Services\MachineService;
use Livewire\Component;
use Livewire\WithPagination;

class MachineManagement extends Component
{
    use WithPagination;

    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
        'branchSelected' => 'handleBranchSelected',
    ];

    public MachineForm $form;

    public string $search = '';

    public string $branchId = 'all';

    public bool $showDeleted = false;

    public bool $showFormModal = false;

    public ?string $editingMachineId = null;

    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    private MachineService $machineService;

    /**
     * Dependency injection via Livewire boot method.
     */
    public function boot(MachineService $machineService): void
    {
        $this->machineService = $machineService;
    }

    /**
     * Authorize access on mount and scope branch.
     */
    public function mount(?string $branchId = null): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized access.');
        }

        if ($role === 'manager') {
            $this->branchId = (string) auth()->user()->branch_id;
        } else {
            $this->branchId = $branchId ?: 'all';
        }
    }

    public function handleBranchSelected(string $branchId): void
    {
        if (auth()->user()->role === 'owner') {
            $this->branchId = $branchId;
            $this->resetPage();
            $this->showSuccessAlert = false;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowDeleted(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->form->resetFields();

        // Scope to active user's branch for managers
        if (auth()->user()->role === 'manager') {
            $this->form->branch_id = (string) auth()->user()->branch_id;
        } elseif ($this->branchId !== 'all') {
            $this->form->branch_id = $this->branchId;
        }

        $this->editingMachineId = null;
        $this->showFormModal = true;
        $this->showSuccessAlert = false;
    }

    public function openEditModal(string $machineId): void
    {
        $this->resetErrorBag();

        $query = Machine::query();
        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        $machine = $query->findOrFail($machineId);

        // Branch Isolation scope check
        if (auth()->user()->role === 'manager' && $machine->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $this->editingMachineId = $machine->id;
        $this->form->setMachine($machine);
        $this->showFormModal = true;
        $this->showSuccessAlert = false;
    }

    public function saveMachine(): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $payload = $this->form->validateAndGetPayload();

        // Enforce branch safety for manager
        if ($role === 'manager') {
            $payload['branch_id'] = (string) auth()->user()->branch_id;
        }

        if ($this->editingMachineId) {
            $machine = Machine::withTrashed()->findOrFail($this->editingMachineId);
            if ($role === 'manager' && $machine->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized branch access.');
            }
            $this->machineService->updateMachine($machine, $payload, auth()->user());
            $this->successMessage = 'Printer machine updated successfully!';
        } else {
            $this->machineService->createMachine($payload, auth()->user());
            $this->successMessage = 'Printer machine registered successfully!';
        }

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->showFormModal = false;
        $this->showSuccessAlert = true;
    }

    public function deleteMachine(string $machineId): void
    {
        $machine = Machine::findOrFail($machineId);

        if (auth()->user()->role === 'manager' && $machine->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $this->machineService->deleteMachine($machine, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'Printer machine deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function restoreMachine(string $machineId): void
    {
        $machine = Machine::onlyTrashed()->findOrFail($machineId);

        if (auth()->user()->role === 'manager' && $machine->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $this->machineService->restoreMachine($machine, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'Printer machine restored successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $query = Machine::query()->with('branch');

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        // Branch Isolation filtering
        if (auth()->user()->role === 'manager') {
            $query->where('branch_id', auth()->user()->branch_id);
        } elseif ($this->branchId !== 'all') {
            $query->where('branch_id', $this->branchId);
        }

        if (! empty($this->search)) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        $machinesList = $query->orderBy('name')->paginate(15);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.machine-management', compact('machinesList', 'branches'));
    }
}
