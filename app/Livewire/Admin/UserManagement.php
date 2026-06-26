<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserManagement extends Component
{
    /**
     * Operation mode: 'owner' or 'manager'.
     * Controls RBAC rules, role dropdown options, and branch selector visibility.
     */
    public string $mode = 'owner';

    // Search & list
    public string $userSearch = '';

    // Modal state
    public bool $showUserModal = false;

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

    private function isPrimaryOwner($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->role === 'owner' && ($user->username === 'admin_owner' || $user->username === 'primary' || $user->id === 1 || $user->id === '1');
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
            'formUsername' => 'required|string|max:255|unique:users,username,'.($this->userIdBeingEdited ?: 'NULL').',id,deleted_at,NULL',
            'formEmail' => 'required|string|email|max:255|unique:users,email,'.($this->userIdBeingEdited ?: 'NULL').',id,deleted_at,NULL',
            'formPhone' => 'nullable|string|max:20',
            'formAddress' => 'nullable|string|max:255',
            'formRole' => 'required|string|in:'.$allowedRoles,
        ];

        if ($this->mode === 'owner') {
            $rules['formBranchId'] = 'required|string|uuid|exists:branches,id';
        }

        $rules['formPassword'] = $this->userIdBeingEdited
            ? 'nullable|string|min:6'
            : 'required|string|min:6';

        $this->validate($rules);

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
                $data['branch_id'] = $this->formBranchId;
            }

            if (! empty($this->formPassword)) {
                $data['password'] = Hash::make($this->formPassword);
            }

            $user->update($data);
            $this->successMessage = 'User updated successfully!';
        } else {
            // Secondary owner cannot create owner accounts
            if ($this->mode === 'owner' && $this->formRole === 'owner' && ! $this->isPrimaryOwner(auth()->user())) {
                abort(403, 'Unauthorized.');
            }

            $branchId = $this->mode === 'owner'
                ? $this->formBranchId
                : (auth()->user()->branch_id ?: Branch::value('id'));

            User::create([
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
                'branch_id' => $branchId,
                'password' => Hash::make($this->formPassword),
            ]);
            $this->successMessage = 'User created successfully!';
        }

        $this->showUserModal = false;
        $this->showSuccessAlert = true;
    }

    public function deleteUser(string $userId): void
    {
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

        $user->delete();

        $this->successMessage = 'User deleted successfully!';
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
            if (! empty($this->userSearch)) {
                $query->where(function ($q) {
                    $q->where('users.name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('users.username', 'like', '%'.$this->userSearch.'%');
                });
            }
            $usersList = $query->orderBy('users.name')->get();
        } else {
            // Manager sees only own branch users
            $query = User::where('branch_id', $branchId ?: Branch::value('id'));
            if (! empty($this->userSearch)) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->userSearch.'%')
                        ->orWhere('username', 'like', '%'.$this->userSearch.'%');
                });
            }
            $usersList = $query->orderBy('name')->get();
        }

        $allBranches = Branch::all();

        return view('livewire.admin.user-management', compact('usersList', 'allBranches'));
    }
}
