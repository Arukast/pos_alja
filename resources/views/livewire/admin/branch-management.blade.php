<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text space-y-6 rounded-2xl border bg-white p-6 text-xs shadow-sm dark:text-gray-100"
>
    @include('livewire.admin.partials.branch-management._alerts') {{-- Search &
    Add --}}
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex max-w-xl flex-1 flex-wrap items-center gap-4">
            <div class="relative min-w-[200px] flex-1">
                <x-text-input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search branch name or code..."
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

            <label
                class="inline-flex cursor-pointer items-center space-x-2 select-none"
            >
                <input
                    type="checkbox"
                    wire:model.live="showDeactivated"
                    class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-brand-primary focus:ring-brand-primary rounded"
                />
                <span
                    class="text-light-text text-xs font-semibold dark:text-gray-300"
                    >Show Deleted Branches</span
                >
            </label>
        </div>
        <x-primary-button
            type="button"
            wire:click="openCreateModal"
            class="!px-4 !py-2 !text-[11px] !font-bold !tracking-wider"
        >
            Add Branch
        </x-primary-button>
    </div>

    {{-- Data Table --}}
    <div
        class="dark:border-surface-300/10 overflow-x-auto rounded-xl border border-gray-100"
    >
        <table class="w-full border-collapse text-left">
            <thead>
                <tr
                    class="dark:bg-surface-500/20 text-light-muted dark:border-surface-300/10 border-b border-gray-100 bg-gray-50/50 text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >
                    <th class="p-3">Branch Name</th>
                    <th class="p-3">Code</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Address</th>
                    <th class="p-3">Tax Rate</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody
                class="dark:divide-surface-300/10 divide-y divide-gray-100 font-medium"
            >
                @forelse ($branchesList as $branch)
                <tr
                    class="dark:hover:bg-surface-500/10 transition-colors hover:bg-gray-50/50"
                >
                    <td class="p-3">
                        <span class="text-light-text font-bold dark:text-white"
                            >{{ $branch->name }}</span
                        >
                    </td>
                    <td class="p-3">
                        <span
                            class="dark:bg-surface-500 text-light-text dark:text-gray-350 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px]"
                            >{{ $branch->code ?? '-' }}</span
                        >
                    </td>
                    <td class="text-light-muted p-3 dark:text-gray-400">
                        {{ $branch->phone ?? '-' }}
                    </td>
                    <td
                        class="text-light-muted max-w-[200px] truncate p-3 dark:text-gray-400"
                    >
                        {{ $branch->address ?? '-' }}
                    </td>
                    <td
                        class="text-light-text p-3 font-semibold dark:text-gray-300"
                    >
                        {{ number_format($branch->settings['tax_rate'] ?? 0, 2)
                        }}%
                    </td>
                    <td class="p-3">
                        @if ($branch->deleted_at)
                        <span
                            class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 dark:bg-red-950/30 dark:text-red-400"
                        >
                            Deleted
                        </span>
                        @else
                        <button
                            type="button"
                            wire:click="toggleBranchStatus('{{ $branch->id }}')"
                            class="inline-flex cursor-pointer items-center rounded-full px-2 py-0.5 text-[10px] font-bold transition-all {{ $branch->is_active ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 hover:bg-green-100' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 hover:bg-amber-100' }}"
                        >
                            {{ $branch->is_active ? 'Active' : 'Inactive' }}
                        </button>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if (!$branch->deleted_at)
                            <button
                                type="button"
                                wire:click="openEditModal('{{ $branch->id }}')"
                                class="text-brand-primary hover:text-brand-primary/80 font-bold transition-colors"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                wire:click="deleteBranch('{{ $branch->id }}')"
                                wire:confirm="Are you sure you want to delete this branch? This action will perform a soft-delete."
                                class="font-bold text-red-500 transition-colors hover:text-red-700"
                            >
                                Delete
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td
                        colspan="7"
                        class="text-light-muted p-8 text-center dark:text-gray-400"
                    >
                        No physical branches found matching your search.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}} @if ($branchesList->hasPages())
    <div class="mt-4">{{ $branchesList->links() }}</div>
    @endif @include('livewire.admin.partials.branch-management._form_modal')
</div>
