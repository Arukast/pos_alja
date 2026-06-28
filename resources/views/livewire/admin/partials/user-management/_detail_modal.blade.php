{{-- VIEW USER DETAIL MODAL --}} @if ($showDetailModal &&
$selectedUserForDetail) @php $isSelf = ($selectedUserForDetail->id ===
auth()->user()->id); $isRestricted = $mode === 'owner' ?
($selectedUserForDetail->role === 'owner' &&
!$this->isPrimaryOwner(auth()->user())) : in_array($selectedUserForDetail->role,
['owner', 'manager']); @endphp
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card animate-in fade-in zoom-in relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs duration-200"
    >
        <x-brand-stripe />

        {{-- Modal Header --}}
        <div
            class="border-light-border dark:border-surface-300/10 mb-5 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Staff Member Details
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    Detailed profile configuration and operational scoping.
                </p>
            </div>
            <button
                wire:click="closeDetailModal"
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

        {{-- Details Grid --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Full Name</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                        >{{ $selectedUserForDetail->name }}</span
                    >
                </div>
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Username</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                        >{{ $selectedUserForDetail->username }}</span
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Email Address</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                        >{{ $selectedUserForDetail->email ?: '-' }}</span
                    >
                </div>
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Staff Role</span
                    >
                    <div class="mt-1 flex items-center">
                        <x-user-management.role-badge
                            :role="$selectedUserForDetail->role"
                            class="!px-2.5"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Phone Number</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                        >{{ $selectedUserForDetail->phone ?: '-' }}</span
                    >
                </div>
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Date Joined</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                        >{{ $selectedUserForDetail->created_at ?
                        $selectedUserForDetail->created_at->format('M d, Y H:i')
                        : '-' }}</span
                    >
                </div>
            </div>

            @if ($mode === 'owner')
            <div
                class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
            >
                <span
                    class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Assigned Branch</span
                >
                <span
                    class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                    >{{ $selectedUserForDetail->branch_name ?? 'Global' }}</span
                >
            </div>
            @endif

            <div
                class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
            >
                <span
                    class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Home Address</span
                >
                <span
                    class="text-light-text mt-1 block text-sm font-semibold whitespace-pre-wrap dark:text-white"
                    >{{ $selectedUserForDetail->address ?: '-' }}</span
                >
            </div>
        </div>

        {{-- Modal Actions --}}
        <div
            class="border-light-border dark:border-surface-300/10 mt-6 flex items-center justify-between border-t pt-4"
        >
            <div>
                @if ($isRestricted)
                <div
                    class="flex items-center gap-1.5 text-[10px] font-semibold text-red-500 dark:text-red-400"
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
                    <span>Insufficient privileges.</span>
                </div>
                @endif
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="closeDetailModal"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Close
                </button>

                @if (!$isRestricted)
                <button
                    type="button"
                    wire:click="openEditModal('{{ $selectedUserForDetail->id }}')"
                    class="bg-brand-secondary hover:bg-brand-secondary-hover cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase transition duration-150"
                >
                    Edit
                </button>

                @if (!$isSelf)
                <button
                    type="button"
                    wire:click="deleteUser('{{ $selectedUserForDetail->id }}')"
                    onclick="
                        confirm('Are you sure you want to delete this user?') ||
                            event.stopImmediatePropagation()
                    "
                    class="hover:bg-red-650 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase transition duration-150"
                >
                    Delete
                </button>
                @endif @endif
            </div>
        </div>
    </div>
</div>
@endif
