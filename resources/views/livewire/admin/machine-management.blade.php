<div class="space-y-6">
    <!-- Feedback alert -->
    <x-manager.feedback-alert
        :show="$showSuccessAlert"
        :message="$successMessage"
    />

    <!-- Machines list card -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div
            class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Printer & Press Machines
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Register and manage the machinery used in production, and
                    set their base cost (HPP) per run.
                </p>
            </div>
            <div>
                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Register Machine
                </button>
            </div>
        </div>

        <!-- Filters & Search Toolbar -->
        <div
            class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex max-w-xl flex-1 flex-wrap items-center gap-4">
                <div class="relative min-w-[200px] flex-1">
                    <x-text-input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search machine name..."
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

                @if (auth()->user()->role === 'owner')
                <div class="min-w-[150px]">
                    <select
                        wire:model.live="branchId"
                        class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 focus:ring-brand-primary text-light-text w-full rounded-xl border bg-white px-3 py-1.5 text-xs font-semibold focus:ring-1 focus:outline-none dark:text-white"
                    >
                        <option value="all">All Branches</option>
                        <option value="">Global Only</option>
                        @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <label
                    class="inline-flex cursor-pointer items-center space-x-2 select-none"
                >
                    <input
                        type="checkbox"
                        wire:model.live="showDeleted"
                        class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-brand-primary focus:ring-brand-primary rounded"
                    />
                    <span
                        class="text-light-text text-xs font-semibold dark:text-gray-300"
                        >Show Deleted</span
                    >
                </label>
            </div>
        </div>

        <!-- Table -->
        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Machine Name</th>
                        <th class="px-4 py-3">Branch Location</th>
                        <th class="px-4 py-3">Base Cost (HPP)</th>
                        <th class="rounded-r-xl px-4 py-3 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($machinesList as $machine)
                    <tr
                        class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 even:bg-light-bg/50 dark:even:bg-surface-500/20 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $machine->name }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            @if ($machine->branch_id)
                            <span class="text-gray-600 dark:text-gray-300"
                                >{{ $machine->branch->name }}</span
                            >
                            @else
                            <span
                                class="rounded-md bg-blue-50 px-2 py-0.5 text-[9px] font-bold text-blue-600 dark:bg-blue-950/20 dark:text-blue-400"
                            >
                                Global
                            </span>
                            @endif
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-semibold dark:text-white"
                        >
                            Rp {{ number_format($machine->cost, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if ($machine->deleted_at)
                                <button
                                    type="button"
                                    wire:click="restoreMachine('{{ $machine->id }}')"
                                    class="cursor-pointer font-bold text-green-600 transition-colors hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                                >
                                    Restore
                                </button>
                                @else
                                <button
                                    type="button"
                                    wire:click="openEditModal('{{ $machine->id }}')"
                                    class="cursor-pointer font-bold text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Edit
                                </button>
                                <span class="text-gray-350 dark:text-gray-600"
                                    >|</span
                                >
                                <button
                                    type="button"
                                    wire:confirm="Are you sure you want to delete this machine?"
                                    wire:click="deleteMachine('{{ $machine->id }}')"
                                    class="cursor-pointer font-bold text-red-600 transition-colors hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
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
                            colspan="4"
                            class="text-light-muted px-4 py-6 text-center text-xs font-semibold dark:text-gray-400"
                        >
                            No print machines found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $machinesList->links() }}</div>
    </div>

    <!-- Modal Form -->
    @if ($showFormModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4 backdrop-blur-xs"
    >
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 w-full max-w-md space-y-4 rounded-2xl border bg-white p-6 shadow-xl"
        >
            <div
                class="dark:border-surface-300/10 flex items-center justify-between border-b border-gray-100 pb-3"
            >
                <h3 class="text-light-text text-base font-bold dark:text-white">
                    {{ $editingMachineId ? 'Edit Printer Machine' : 'Register
                    Printer Machine' }}
                </h3>
                <button
                    type="button"
                    wire:click="$set('showFormModal', false)"
                    class="text-light-muted hover:text-light-text dark:text-gray-450 text-lg font-bold dark:hover:text-white"
                >
                    &times;
                </button>
            </div>

            <form wire:submit.prevent="saveMachine" class="space-y-4 text-left">
                <div>
                    <x-input-label for="machine-name" value="Machine Name" />
                    <x-text-input
                        id="machine-name"
                        type="text"
                        wire:model="form.name"
                        class="mt-1 w-full"
                        placeholder="e.g. Roland VersaUV LEF-200"
                    />
                    <x-input-error
                        :messages="$errors->get('form.name')"
                        class="mt-1"
                    />
                </div>

                @if (auth()->user()->role === 'owner')
                <div>
                    <x-input-label
                        for="machine-branch"
                        value="Branch Location"
                    />
                    <select
                        id="machine-branch"
                        wire:model="form.branch_id"
                        class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 focus:ring-brand-primary text-light-text mt-1 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold focus:ring-1 focus:outline-none dark:text-white"
                    >
                        <option value="">Global (All Branches)</option>
                        @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                    <x-input-error
                        :messages="$errors->get('form.branch_id')"
                        class="mt-1"
                    />
                </div>
                @else
                <!-- Managers are locked to their own branch -->
                <input type="hidden" wire:model="form.branch_id" />
                @endif

                <div>
                    <x-input-label
                        for="machine-cost"
                        value="Base Cost per Use (HPP)"
                    />
                    <x-text-input
                        id="machine-cost"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="form.cost"
                        class="mt-1 w-full"
                        placeholder="0.00"
                    />
                    <x-input-error
                        :messages="$errors->get('form.cost')"
                        class="mt-1"
                    />
                </div>

                <div
                    class="dark:border-surface-300/10 flex items-center justify-end gap-3 border-t border-gray-100 pt-3"
                >
                    <button
                        type="button"
                        wire:click="$set('showFormModal', false)"
                        class="border-light-border dark:border-surface-300/30 text-light-text dark:hover:bg-surface-500 rounded-xl border px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition-colors hover:bg-gray-50 dark:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="bg-brand-primary hover:bg-brand-primary/95 rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all"
                    >
                        Save Machine
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
