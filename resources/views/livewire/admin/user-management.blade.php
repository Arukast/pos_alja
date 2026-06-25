<div
    class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card space-y-6 rounded-xl border border-gray-200 bg-white p-6 text-xs text-gray-200 shadow-sm backdrop-blur-sm"
>
    {{-- Success Alert --}} @if ($showSuccessAlert)
    <div
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-400"
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
            <span class="text-sm font-semibold text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="text-emerald-400 hover:text-emerald-300"
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
            <input
                type="text"
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Search staff name or username..."
                class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 pl-9 text-xs text-gray-800 placeholder-gray-400 transition-colors duration-200 focus:ring-1 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
            />
            <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"
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
        <button
            wire:click="openCreateModal"
            class="bg-brand-primary hover:bg-brand-primary-hover shadow-glow-primary rounded-xl px-4 py-2 text-[11px] font-bold tracking-wider text-white uppercase transition duration-150 active:scale-[0.98]"
        >
            Add Staff Member
        </button>
    </div>

    {{-- Users Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-400">
            <thead
                class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
            >
                <tr>
                    <th class="rounded-l-lg px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Username</th>
                    <th class="px-3 py-2.5">Email</th>
                    <th class="px-3 py-2.5">Role</th>
                    @if ($mode === 'owner')
                    <th class="px-3 py-2.5">Branch</th>
                    @endif
                    <th class="px-3 py-2.5">Phone</th>
                    <th class="px-3 py-2.5">Address</th>
                    <th class="rounded-r-lg px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody
                class="dark:divide-surface-300/30 divide-y divide-gray-100 text-[11px]"
            >
                @forelse ($usersList as $u) @php $isSelf = ($u->id ===
                auth()->user()->id); $isRestricted = $mode === 'owner' ?
                ($u->role === 'owner' && auth()->user()->id !== 1) :
                in_array($u->role, ['owner', 'manager']); @endphp
                <tr
                    class="dark:hover:bg-surface-400/20 transition-colors duration-150 hover:bg-gray-50"
                >
                    <td class="px-3 py-2.5 font-semibold text-white">
                        {{ $u->name }}
                    </td>
                    <td class="px-3 py-2.5">{{ $u->username }}</td>
                    <td class="px-3 py-2.5">{{ $u->email }}</td>
                    <td class="px-3 py-2.5">
                        <span
                            class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                {{ $u->role === 'owner' ? 'bg-red-500/10 text-red-400' : '' }}
                                {{ $u->role === 'manager' ? 'bg-orange-500/10 text-orange-400' : '' }}
                                {{ $u->role === 'cashier' ? 'bg-brand-secondary/10 text-brand-secondary' : '' }}
                                {{ $u->role === 'designer' ? 'bg-brand-primary/10 text-brand-primary' : '' }}
                                {{ $u->role === 'production' ? 'bg-brand-accent/10 text-brand-accent' : '' }}
                            "
                        >
                            {{ $u->role }}
                        </span>
                    </td>
                    @if ($mode === 'owner')
                    <td class="px-3 py-2.5 font-semibold text-gray-300">
                        {{ $u->branch_name ?? 'Global' }}
                    </td>
                    @endif
                    <td class="px-3 py-2.5">{{ $u->phone ?: '-' }}</td>
                    <td class="max-w-[150px] truncate px-3 py-2.5">
                        {{ $u->address ?: '-' }}
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        @if ($isRestricted)
                        <div
                            class="flex justify-end pr-4 text-gray-500"
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
                        <div class="flex justify-end gap-2">
                            <button
                                wire:click="openEditModal({{ $u->id }})"
                                class="text-brand-secondary hover:text-brand-secondary-light text-[10px] font-semibold tracking-wider uppercase"
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
                                class="text-[10px] font-semibold tracking-wider text-red-400 uppercase hover:text-red-300"
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
                        class="py-4 text-center text-gray-500"
                    >
                        No users found matching search query.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ADD/EDIT USER MODAL --}} @if ($showUserModal)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
>
    <div
        class="dark:bg-surface-650 dark:border-surface-300/40 relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 text-xs text-gray-800 shadow-2xl dark:text-gray-200"
    >
        {{-- Modal Header --}}
        <div
            class="border-gray-150 dark:border-gray-750 mb-4 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                    {{ $userIdBeingEdited ? 'Edit User Account' : 'Add User
                    Account' }}
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Configure user settings, role classification, and branch
                    scoping.
                </p>
            </div>
            {{-- Alpine-driven close: eliminates server round-trip for UI-only
            toggle --}}
            <button
                @click="$wire.set('showUserModal', false)"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
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
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Full Name</label
                    >
                    <input
                        type="text"
                        wire:model="formName"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                        required
                    />
                    @error('formName')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Username</label
                    >
                    <input
                        type="text"
                        wire:model="formUsername"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
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
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Email Address</label
                    >
                    <input
                        type="email"
                        wire:model="formEmail"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                        required
                    />
                    @error('formEmail')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Staff Role</label
                    >
                    <select
                        wire:model="formRole"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                    >
                        @if ($mode === 'owner' && auth()->user()->id === 1)
                        <option value="owner" class="bg-white text-gray-900">
                            Owner (owner)
                        </option>
                        @endif @if ($mode === 'owner')
                        <option value="manager" class="bg-white text-gray-900">
                            Manager (manager)
                        </option>
                        @endif
                        <option value="cashier" class="bg-white text-gray-900">
                            Cashier (cashier)
                        </option>
                        <option value="designer" class="bg-white text-gray-900">
                            Designer (designer)
                        </option>
                        <option
                            value="production"
                            class="bg-white text-gray-900"
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
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Password {{ $userIdBeingEdited ? '(Leave blank to keep
                        current)' : '' }}</label
                    >
                    @if ($userIdBeingEdited)
                    <input
                        type="password"
                        wire:model="formPassword"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                    />
                    @else
                    <input
                        type="password"
                        wire:model="formPassword"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                        required
                    />
                    @endif @error('formPassword')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Phone Number</label
                    >
                    <input
                        type="text"
                        wire:model="formPhone"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
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
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Assign Branch</label
                    >
                    <select
                        wire:model="formBranchId"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                    >
                        @foreach ($allBranches as $branch)
                        <option
                            value="{{ $branch->id }}"
                            class="bg-white text-gray-900"
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
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Address</label
                    >
                    <input
                        type="text"
                        wire:model="formAddress"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:outline-none dark:text-gray-100"
                    />
                    @error('formAddress')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div
                class="border-gray-150 dark:border-gray-750 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                {{-- Alpine-driven cancel: eliminates unnecessary server
                round-trip --}}
                <button
                    type="button"
                    @click="$wire.set('showUserModal', false)"
                    class="dark:border-surface-300/40 dark:bg-surface-600 dark:hover:bg-surface-500 rounded-xl border border-gray-300 bg-white px-4 py-2 text-[10px] font-bold tracking-wider text-gray-800 uppercase transition duration-150 hover:bg-gray-100 dark:text-white"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="bg-brand-primary hover:bg-brand-primary-hover shadow-glow-primary rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase transition duration-150 active:scale-[0.98]"
                >
                    Save Staff
                </button>
            </div>
        </form>
    </div>
</div>
@endif
