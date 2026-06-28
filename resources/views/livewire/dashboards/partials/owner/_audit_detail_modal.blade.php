@if ($showAuditDetailModal && $selectedAuditLog)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card animate-in fade-in zoom-in relative w-full max-w-2xl overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs duration-200"
    >
        <x-brand-stripe />

        {{-- Modal Header --}}
        <div
            class="border-light-border dark:border-surface-300/10 mb-5 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    System Activity Log Details
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    Detailed log metadata and state changes payload.
                </p>
            </div>
            <button
                wire:click="closeAuditDetail"
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
                        >Timestamp</span
                    >
                    <!-- prettier-ignore -->
                    <span class="text-light-text mt-1 block text-sm font-semibold dark:text-white">
                        {{ $selectedAuditLog->created_at->format('Y-m-d H:i:s') }}
                    </span>
                </div>
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >User (Username)</span
                    >
                    <!-- prettier-ignore -->
                    <span class="text-light-text mt-1 block text-sm font-semibold dark:text-white">
                        {{ $selectedAuditLog->username }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Role</span
                    >
                    <!-- prettier-ignore -->
                    <span class="text-light-text mt-1 block text-sm font-semibold uppercase dark:text-white">
                        {{ $selectedAuditLog->role }}
                    </span>
                </div>
                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Action</span
                    >
                    <!-- prettier-ignore -->
                    <span class="text-brand-secondary mt-1 block text-sm font-bold uppercase">
                        {{ $selectedAuditLog->action }}
                    </span>
                </div>
            </div>

            <div
                class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
            >
                <span
                    class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Description</span
                >
                <!-- prettier-ignore -->
                <span class="text-light-text mt-1 block text-sm font-semibold whitespace-pre-wrap dark:text-white">
                    {{ $selectedAuditLog->description }}
                </span>
            </div>

            <div
                class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
            >
                <span
                    class="text-light-muted mb-2 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Payload Data</span
                >
                @if ($selectedAuditLog->payload)
                <!-- prettier-ignore -->
                <pre class="dark:bg-surface-650 border-light-border dark:border-surface-300/10 text-light-text max-h-60 overflow-auto rounded-lg border bg-white p-3 font-mono text-[10px] whitespace-pre dark:text-gray-300"><code>{{ json_encode($selectedAuditLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                @else
                <span
                    class="text-light-muted mt-1 block text-sm font-semibold dark:text-gray-400"
                    >-</span
                >
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div
            class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end border-t pt-4"
        >
            <button
                type="button"
                wire:click="closeAuditDetail"
                class="border-light-border hover:bg-light-surface dark:border-surface-300/20 dark:hover:bg-surface-500/20 rounded-xl border px-4 py-2 font-bold transition-colors"
            >
                Close
            </button>
        </div>
    </div>
</div>
@endif
