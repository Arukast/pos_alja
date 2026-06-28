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
                ($u->role === 'owner' && !$this->isPrimaryOwner(auth()->user()))
                : in_array($u->role, ['owner', 'manager']); @endphp
                <tr
                    class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 even:bg-light-surface-2/30 dark:even:bg-surface-500/20 transition-colors duration-150"
                >
                    <td
                        class="text-light-text px-4 py-3 font-semibold dark:text-white"
                    >
                        {{ $u->name }}
                    </td>
                    <td class="text-light-muted px-4 py-3 dark:text-gray-300">
                        {{ $u->username }}
                    </td>
                    <td class="text-light-muted px-4 py-3 dark:text-gray-300">
                        {{ $u->email }}
                    </td>
                    <td class="px-4 py-3">
                        <x-user-management.role-badge :role="$u->role" />
                    </td>
                    @if ($mode === 'owner')
                    <td
                        class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                    >
                        {{ $u->branch_name ?? 'Global' }}
                    </td>
                    @endif
                    <td class="text-light-muted px-4 py-3 dark:text-gray-300">
                        {{ $u->phone ?: '-' }}
                    </td>
                    <td
                        class="text-light-muted max-w-[150px] truncate px-4 py-3 dark:text-gray-300"
                    >
                        {{ $u->address ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-3.5">
                            @if ($showDeactivated) @php $isRestricted = $mode
                            === 'owner' ? ($u->role === 'owner' &&
                            !$this->isPrimaryOwner(auth()->user())) :
                            in_array($u->role, ['owner', 'manager']); @endphp
                            @if ($isRestricted)
                            <div
                                class="text-light-muted flex justify-end pr-4 dark:text-gray-500"
                                title="Security Lock: Insufficient privileges to restore this account"
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
                                    />
                                </svg>
                            </div>
                            @else
                            <button
                                wire:click="restoreUser('{{ $u->id }}')"
                                class="text-emerald-650 cursor-pointer text-xs font-bold tracking-wider uppercase transition-colors duration-150 hover:text-emerald-500"
                            >
                                Restore
                            </button>
                            @endif @else
                            <button
                                wire:click="openDetailModal('{{ $u->id }}')"
                                class="text-brand-primary hover:text-brand-primary-hover cursor-pointer text-xs font-bold tracking-wider uppercase transition-colors duration-150"
                            >
                                Detail
                            </button>
                            @endif
                        </div>
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
    @if ($usersList->hasPages())
    <div
        class="border-light-border dark:border-surface-300/10 border-t px-4 py-3"
    >
        {{ $usersList->links() }}
    </div>
    @endif
</div>
