<div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card space-y-6 text-xs text-gray-200">
    {{-- Success Alert --}}
    @if ($showSuccessAlert)
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm flex justify-between items-center transition" x-data="{ show: true }" x-show="show">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-semibold text-emerald-300">{{ $successMessage }}</span>
            </div>
            <button type="button" @click="show = false; $wire.set('showSuccessAlert', false)" class="text-emerald-400 hover:text-emerald-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    @endif

    {{-- Search & Add --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <input
                type="text"
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Search staff name or username..."
                class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 pl-9 focus:outline-none focus:border-alja-magenta focus:ring-1 focus:ring-alja-magenta/30 transition-colors duration-200 text-xs"
            >
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <button wire:click="openCreateModal" class="rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white font-bold py-2 px-4 shadow-glow-magenta active:scale-[0.98] transition duration-150 uppercase tracking-wider text-[11px]">
            Add Staff Member
        </button>
    </div>

    {{-- Users Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-400">
            <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                <tr>
                    <th class="py-2.5 px-3 rounded-l-lg">Name</th>
                    <th class="py-2.5 px-3">Username</th>
                    <th class="py-2.5 px-3">Email</th>
                    <th class="py-2.5 px-3">Role</th>
                    @if ($mode === 'owner')
                        <th class="py-2.5 px-3">Branch</th>
                    @endif
                    <th class="py-2.5 px-3">Phone</th>
                    <th class="py-2.5 px-3">Address</th>
                    <th class="py-2.5 px-3 text-right rounded-r-lg">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30 text-[11px]">
                @forelse ($usersList as $u)
                    @php
                        $isSelf = ($u->id === auth()->user()->id);
                        $isRestricted = $mode === 'owner'
                            ? ($u->role === 'owner' && auth()->user()->id !== 1)
                            : in_array($u->role, ['owner', 'manager']);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-colors duration-150">
                        <td class="py-2.5 px-3 font-semibold text-white">{{ $u->name }}</td>
                        <td class="py-2.5 px-3">{{ $u->username }}</td>
                        <td class="py-2.5 px-3">{{ $u->email }}</td>
                        <td class="py-2.5 px-3">
                            <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                {{ $u->role === 'owner' ? 'bg-red-500/10 text-red-400' : '' }}
                                {{ $u->role === 'manager' ? 'bg-orange-500/10 text-orange-400' : '' }}
                                {{ $u->role === 'cashier' ? 'bg-alja-cyan/10 text-alja-cyan' : '' }}
                                {{ $u->role === 'designer' ? 'bg-alja-magenta/10 text-alja-magenta' : '' }}
                                {{ $u->role === 'production' ? 'bg-alja-yellow/10 text-alja-yellow' : '' }}
                            ">
                                {{ $u->role }}
                            </span>
                        </td>
                        @if ($mode === 'owner')
                            <td class="py-2.5 px-3 text-gray-300 font-semibold">{{ $u->branch_name ?? 'Global' }}</td>
                        @endif
                        <td class="py-2.5 px-3">{{ $u->phone ?: '-' }}</td>
                        <td class="py-2.5 px-3 truncate max-w-[150px]">{{ $u->address ?: '-' }}</td>
                        <td class="py-2.5 px-3 text-right">
                            @if ($isRestricted)
                                <div class="flex justify-end text-gray-500 pr-4" title="Security Lock: Insufficient privileges to manage this account">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                            @else
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEditModal({{ $u->id }})" class="text-alja-cyan hover:text-alja-cyan-light font-semibold uppercase tracking-wider text-[10px]">Edit</button>
                                    @if (!$isSelf)
                                        <button
                                            wire:click="deleteUser({{ $u->id }})"
                                            onclick="confirm('Are you sure you want to delete this user?') || event.stopImmediatePropagation()"
                                            class="text-red-400 hover:text-red-300 font-semibold uppercase tracking-wider text-[10px]"
                                        >Delete</button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $mode === 'owner' ? 8 : 7 }}" class="py-4 text-center text-gray-500">No users found matching search query.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ADD/EDIT USER MODAL --}}
@if ($showUserModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-surface-650 border border-gray-200 dark:border-surface-300/40 shadow-2xl p-6 relative text-xs text-gray-800 dark:text-gray-200">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-gray-150 dark:border-gray-750 pb-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ $userIdBeingEdited ? 'Edit User Account' : 'Add User Account' }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Configure user settings, role classification, and branch scoping.</p>
                </div>
                {{-- Alpine-driven close: eliminates server round-trip for UI-only toggle --}}
                <button @click="$wire.set('showUserModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Modal Form --}}
            <form wire:submit.prevent="saveUser" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Full Name</label>
                        <input type="text" wire:model="formName" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                        @error('formName') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Username</label>
                        <input type="text" wire:model="formUsername" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                        @error('formUsername') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Email Address</label>
                        <input type="email" wire:model="formEmail" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                        @error('formEmail') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Staff Role</label>
                        <select wire:model="formRole" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                            @if ($mode === 'owner' && auth()->user()->id === 1)
                                <option value="owner" class="text-gray-900 bg-white">Owner (owner)</option>
                            @endif
                            @if ($mode === 'owner')
                                <option value="manager" class="text-gray-900 bg-white">Manager (manager)</option>
                            @endif
                            <option value="cashier" class="text-gray-900 bg-white">Cashier (cashier)</option>
                            <option value="designer" class="text-gray-900 bg-white">Designer (designer)</option>
                            <option value="production" class="text-gray-900 bg-white">Production (production)</option>
                        </select>
                        @error('formRole') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Password {{ $userIdBeingEdited ? '(Leave blank to keep current)' : '' }}</label>
                        <input type="password" wire:model="formPassword" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" {{ $userIdBeingEdited ? '' : 'required' }}>
                        @error('formPassword') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Phone Number</label>
                        <input type="text" wire:model="formPhone" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                        @error('formPhone') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @if ($mode === 'owner')
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Assign Branch</label>
                            <select wire:model="formBranchId" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                                @foreach ($allBranches as $branch)
                                    <option value="{{ $branch->id }}" class="text-gray-900 bg-white">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('formBranchId') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    @endif
                    <div class="{{ $mode === 'owner' ? '' : 'col-span-2' }}">
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Address</label>
                        <input type="text" wire:model="formAddress" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                        @error('formAddress') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-gray-150 dark:border-gray-750 pt-4">
                    {{-- Alpine-driven cancel: eliminates unnecessary server round-trip --}}
                    <button type="button" @click="$wire.set('showUserModal', false)" class="rounded-xl border border-gray-300 dark:border-surface-300/40 bg-white dark:bg-surface-600 text-gray-800 dark:text-white px-4 py-2 hover:bg-gray-100 dark:hover:bg-surface-500 transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white px-4 py-2 shadow-glow-magenta active:scale-[0.98] transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                        Save Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
