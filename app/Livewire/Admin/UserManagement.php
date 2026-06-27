<?php

namespace App\Livewire\Admin;

use App\Events\OrderUpdated;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    /**
     * Operation mode: 'owner' or 'manager'.
     * Controls RBAC rules, role dropdown options, and branch selector visibility.
     */
    public string $mode = 'owner';

    // Search & list
    public string $userSearch = '';

    public bool $showDeactivated = false;

    public function updatingUserSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowDeactivated(): void
    {
        $this->resetPage();
    }

    // Modal state
    public bool $showUserModal = false;

    public bool $showDetailModal = false;

    public ?User $selectedUserForDetail = null;

    public ?string $userIdBeingEdited = null;

    // Form fields
    public string $formName = '';

    public string $formUsername = '';

    public string $formEmail = '';

    public string $formPhone = '';

    public string $formAddress = '';

    public string $formPassword = '';

    public string $formRole = 'cashier';

    public string $formBranchId = '';

    // Alert feedback
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function isPrimaryOwner($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->role === 'owner' && ($user->username === 'admin_owner' || $user->username === 'primary' || $user->username === 'owner' || $user->id === 1 || $user->id === '1');
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->userIdBeingEdited = null;
        $this->formName = '';
        $this->formUsername = '';
        $this->formEmail = '';
        $this->formPhone = '';
        $this->formAddress = '';
        $this->formPassword = '';
        $this->formRole = 'cashier';
        $this->formBranchId = auth()->user()->branch_id ?: (Branch::value('id') ?: '');
        $this->showUserModal = true;
    }

    public function openEditModal(string $userId): void
    {
        $this->closeDetailModal();
        $this->resetErrorBag();
        $user = User::findOrFail($userId);

        if ($this->mode === 'owner') {
            // Secondary owners cannot edit other owners
            if ($user->role === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                abort(403, 'Unauthorized.');
            }
        } else {
            // Manager cannot edit owners or other managers
            if (in_array($user->role, ['owner', 'manager'])) {
                abort(403, 'Unauthorized.');
            }
            // Branch scope guard
            if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized.');
            }
        }

        $this->userIdBeingEdited = $user->id;
        $this->formName = $user->name;
        $this->formUsername = $user->username;
        $this->formEmail = $user->email;
        $this->formPhone = $user->phone ?? '';
        $this->formAddress = $user->address ?? '';
        $this->formPassword = '';
        $this->formRole = $user->role;
        $this->formBranchId = $user->branch_id ?: (Branch::value('id') ?: '');
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $allowedRoles = $this->mode === 'owner'
            ? 'owner,manager,cashier,designer,production'
            : 'cashier,designer,production';

        $rules = [
            'formName' => 'required|string|max:255',
            'formUsername' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')
                    ->ignore($this->userIdBeingEdited)
                    ->whereNull('deleted_at'),
            ],
            'formEmail' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->userIdBeingEdited)
                    ->whereNull('deleted_at'),
            ],
            'formPhone' => 'nullable|string|max:20',
            'formAddress' => 'nullable|string|max:255',
            'formRole' => 'required|string|in:'.$allowedRoles,
        ];

        if ($this->mode === 'owner') {
            if ($this->formRole === 'owner') {
                $rules['formBranchId'] = 'nullable|string|uuid|exists:branches,id';
            } else {
                $rules['formBranchId'] = 'required|string|uuid|exists:branches,id';
            }
        }

        $rules['formPassword'] = $this->userIdBeingEdited
            ? 'nullable|string|min:6'
            : 'required|string|min:6';

        $messages = [
            'formName.required' => 'The full name field is required.',
            'formUsername.required' => 'The username field is required.',
            'formUsername.unique' => 'This username has already been taken.',
            'formEmail.required' => 'The email address field is required.',
            'formEmail.email' => 'The email address must be a valid email address.',
            'formEmail.unique' => 'This email address has already been taken.',
            'formRole.required' => 'The staff role field is required.',
            'formPassword.required' => 'The password field is required.',
            'formPassword.min' => 'The password must be at least 6 characters.',
            'formBranchId.required' => 'A physical branch must be assigned for staff roles other than Owner.',
        ];

        $this->validate($rules, $messages);
        $userService = new UserService;

        if ($this->userIdBeingEdited) {
            $user = User::findOrFail($this->userIdBeingEdited);

            if ($this->mode === 'owner') {
                if ($user->role === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                    abort(403, 'Unauthorized.');
                }
                if ($this->formRole === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                    abort(403, 'Unauthorized.');
                }
            } else {
                if (in_array($user->role, ['owner', 'manager'])) {
                    abort(403, 'Unauthorized.');
                }
                if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
                    abort(403, 'Unauthorized.');
                }
            }

            $data = [
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
            ];

            if ($this->mode === 'owner') {
                $data['branch_id'] = $this->formBranchId ?: null;
            } else {
                $data['branch_id'] = $user->branch_id;
            }

            if (! empty($this->formPassword)) {
                $data['password'] = $this->formPassword;
            }

            $userService->updateUser($user, $data, auth()->user());
            $this->successMessage = 'User updated successfully!';
        } else {
            // Secondary owner cannot create owner accounts
            if ($this->mode === 'owner' && $this->formRole === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                abort(403, 'Unauthorized.');
            }

            $branchId = $this->mode === 'owner'
                ? ($this->formBranchId ?: null)
                : (auth()->user()->branch_id ?: Branch::value('id'));

            $data = [
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
                'branch_id' => $branchId,
                'password' => $this->formPassword,
            ];

            $userService->createUser($data, auth()->user());
            $this->successMessage = 'User created successfully!';
        }

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->showUserModal = false;
        $this->showSuccessAlert = true;
    }

    public function deleteUser(string $userId): void
    {
        $this->closeDetailModal();
        $user = User::findOrFail($userId);

        if ($this->mode === 'owner') {
            if ($user->role === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                abort(403, 'Unauthorized.');
            }
        } else {
            if (in_array($user->role, ['owner', 'manager'])) {
                abort(403, 'Unauthorized.');
            }
            if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized.');
            }
        }

        $userService = new UserService;
        $userService->deleteUser($user, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'User deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function openDetailModal(string $userId): void
    {
        $this->selectedUserForDetail = User::withTrashed()
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.*', 'branches.name as branch_name')
            ->where('users.id', $userId)
            ->firstOrFail();

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedUserForDetail = null;
    }

    public function restoreUser(string $userId): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);

        if ($this->mode === 'owner') {
            if ($user->role === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                abort(403, 'Unauthorized.');
            }
        } else {
            if (in_array($user->role, ['owner', 'manager'])) {
                abort(403, 'Unauthorized.');
            }
            if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized.');
            }
        }

        $userService = new UserService;
        $userService->restoreUser($user, auth()->user());

        try {
            event(new OrderUpdated);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->successMessage = 'User restored successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id;

        if ($this->mode === 'owner') {
            // Owner sees all users (cross-branch), with optional search
            $query = User::query()
                ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
                ->select('users.*', 'branches.name as branch_name');
            if ($this->showDeactivated) {
                $query->onlyTrashed();
            }
            if (! empty($this->userSearch)) {
                $query->where(function ($q) {
                    $q->where('users.name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('users.username', 'like', '%'.$this->userSearch.'%');
                });
            }
            $usersList = $query->orderBy('users.name')->paginate(15);
        } else {
            // Manager sees only own branch users
            $query = User::where('branch_id', $branchId ?: Branch::value('id'));
            if ($this->showDeactivated) {
                $query->onlyTrashed();
            }
            if (! empty($this->userSearch)) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('username', 'like', '%'.$this->userSearch.'%');
                });
            }
            $usersList = $query->orderBy('name')->paginate(15);
        }

        $allBranches = Branch::all();

        return view('livewire.admin.user-management', compact('usersList', 'allBranches'));
    }
}
