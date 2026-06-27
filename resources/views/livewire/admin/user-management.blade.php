<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text space-y-6 rounded-2xl border bg-white p-6 text-xs shadow-sm dark:text-gray-100"
>
    {{-- Success Alert --}} @if ($showSuccessAlert)
    <div
        class="border-emerald-250 flex items-center justify-between rounded-xl border bg-emerald-50 p-4 backdrop-blur-sm transition dark:border-emerald-500/20 dark:bg-emerald-950/20"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-600 dark:text-emerald-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
            </svg>
            <span
                class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="hover:text-emerald-750 text-emerald-600 transition-colors dark:text-emerald-400 dark:hover:text-emerald-300"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                ></path>
            </svg>
        </button>
    </div>
    @endif {{-- Search & Add --}}
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="relative max-w-md flex-1">
            <x-text-input
                type="text"
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Search staff name or username..."
                class="w-full !py-2 pl-9 !text-xs"
            />
            <div
                class="text-light-muted pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 dark:text-gray-400"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    ></path>
                </svg>
            </div>
        </div>
        <x-primary-button
            type="button"
            wire:click="openCreateModal"
            class="!px-4 !py-2 !text-[11px] !font-bold !tracking-wider"
        >
            Add Staff Member
        </x-primary-button>
    </div>

    {{-- Users Table --}}
    <div
        class="border-light-border dark:border-surface-300/40 overflow-hidden rounded-2xl border"
    >
        <div class="overflow-x-auto">
            <table
                class="text-light-text dark:text-gray-250 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        @if ($mode === 'owner')
                        <th class="px-4 py-3">Branch</th>
                        @endif
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Address</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/10 divide-y text-[11px]"
                >
                    @forelse ($usersList as $u) @php $isSelf = ($u->id ===
                    auth()->user()->id); $isRestricted = $mode === 'owner' ?
                    ($u->role === 'owner' && auth()->user()->id !== 1) :
                    in_array($u->role, ['owner', 'manager']); @endphp
                    <tr
                        class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 even:bg-light-surface-2/30 dark:even:bg-surface-500/20 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-semibold dark:text-white"
                        >
                            {{ $u->name }}
                        </td>
                        <td
                            class="text-light-muted px-4 py-3 dark:text-gray-300"
                        >
                            {{ $u->username }}
                        </td>
                        <td
                            class="text-light-muted px-4 py-3 dark:text-gray-300"
                        >
                            {{ $u->email }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-block px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-wider
                                    {{ $u->role === 'owner' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $u->role === 'manager' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                    {{ $u->role === 'cashier' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' : '' }}
                                    {{ $u->role === 'designer' ? 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400' : '' }}
                                    {{ $u->role === 'production' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                "
                            >
                                {{ $u->role }}
                            </span>
                        </td>
                        @if ($mode === 'owner')
                        <td
                            class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                        >
                            {{ $u->branch_name ?? 'Global' }}
                        </td>
                        @endif
                        <td
                            class="text-light-muted px-4 py-3 dark:text-gray-300"
                        >
                            {{ $u->phone ?: '-' }}
                        </td>
                        <td
                            class="text-light-muted max-w-[150px] truncate px-4 py-3 dark:text-gray-300"
                        >
                            {{ $u->address ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($isRestricted)
                            <div
                                class="text-light-muted flex justify-end pr-4 dark:text-gray-500"
                                title="Security Lock: Insufficient privileges to manage this account"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    ></path>
                                </svg>
                            </div>
                            @else
                            <div class="flex justify-end gap-3.5">
                                <button
                                    wire:click="openEditModal({{ $u->id }})"
                                    class="text-brand-secondary hover:text-brand-secondary-hover cursor-pointer text-xs font-bold tracking-wider uppercase transition-colors duration-150"
                                >
                                    Edit
                                </button>
                                @if (!$isSelf)
                                <button
                                    wire:click="deleteUser({{ $u->id }})"
                                    onclick="
                                        confirm(
                                            'Are you sure you want to delete this user?'
                                        ) || event.stopImmediatePropagation()
                                    "
                                    class="hover:text-red-650 cursor-pointer text-xs font-bold tracking-wider text-red-500 uppercase transition-colors duration-150 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="{{ $mode === 'owner' ? 8 : 7 }}"
                            class="text-light-muted py-4 text-center dark:text-gray-500"
                        >
                            No users found matching search query.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ADD/EDIT USER MODAL --}} @if ($showUserModal)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs"
    >
        <x-brand-stripe />

        {{-- Modal Header --}}
        <div
            class="border-light-border dark:border-surface-300/10 mb-4 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    {{ $userIdBeingEdited ? 'Edit User Account' : 'Add User
                    Account' }}
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    Configure user settings, role classification, and branch
                    scoping.
                </p>
            </div>
            <button
                @click="$wire.set('showUserModal', false)"
                class="text-light-muted hover:text-light-text dark:text-gray-450 transition-colors dark:hover:text-gray-300"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
            </button>
        </div>

        {{-- Modal Form --}}
        <form wire:submit.prevent="saveUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Full Name</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formName"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formName')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Username</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formUsername"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formUsername')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Email Address</label
                    >
                    <x-text-input
                        type="email"
                        wire:model="formEmail"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formEmail')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Staff Role</label
                    >
                    <select
                        wire:model="formRole"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:text-gray-100"
                    >
                        @if ($mode === 'owner' && auth()->user()->id === 1)
                        <option
                            value="owner"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Owner (owner)
                        </option>
                        @endif @if ($mode === 'owner')
                        <option
                            value="manager"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Manager (manager)
                        </option>
                        @endif
                        <option
                            value="cashier"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Cashier (cashier)
                        </option>
                        <option
                            value="designer"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Designer (designer)
                        </option>
                        <option
                            value="production"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Production (production)
                        </option>
                    </select>
                    @error('formRole')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Password {{ $userIdBeingEdited ? '(Leave blank to keep
                        current)' : '' }}</label
                    >
                    @if ($userIdBeingEdited)
                    <x-text-input
                        type="password"
                        wire:model="formPassword"
                        class="w-full !py-2 !text-xs"
                    />
                    @else
                    <x-text-input
                        type="password"
                        wire:model="formPassword"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @endif @error('formPassword')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Phone Number</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formPhone"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('formPhone')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @if ($mode === 'owner')
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Assign Branch</label
                    >
                    <select
                        wire:model="formBranchId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:text-gray-100"
                    >
                        @foreach ($allBranches as $branch)
                        <option
                            value="{{ $branch->id }}"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('formBranchId')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                @endif
                <div class="{{ $mode === 'owner' ? '' : 'col-span-2' }}">
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Address</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formAddress"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('formAddress')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                <button
                    type="button"
                    @click="$wire.set('showUserModal', false)"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <x-primary-button
                    type="submit"
                    class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                >
                    Save Staff
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endif
