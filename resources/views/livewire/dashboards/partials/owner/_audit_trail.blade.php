<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <div
        class="mb-4 flex flex-col justify-between gap-4 md:flex-row md:items-center"
    >
        <div>
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                System Audit Trail
            </h3>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Real-time logs of system actions and modifications.
            </p>
        </div>
        <div class="w-full md:w-72">
            <input
                type="text"
                wire:model.live.debounce.300ms="auditSearch"
                placeholder="Search logs..."
                class="bg-light-surface-2 dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-light-text focus:ring-brand-primary placeholder-light-muted w-full rounded-xl border px-4 py-2 text-xs focus:ring-1 focus:outline-none dark:text-white dark:placeholder-gray-400"
            />
        </div>
    </div>

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
                    <x-owner.sortable-header
                        field="created_at"
                        :sortField="$auditSortField"
                        :sortDirection="$auditSortDirection"
                        label="Timestamp"
                        class="rounded-l-xl"
                    />
                    <x-owner.sortable-header
                        field="username"
                        :sortField="$auditSortField"
                        :sortDirection="$auditSortDirection"
                        label="User"
                    />
                    <x-owner.sortable-header
                        field="role"
                        :sortField="$auditSortField"
                        :sortDirection="$auditSortDirection"
                        label="Role"
                    />
                    <x-owner.sortable-header
                        field="action"
                        :sortField="$auditSortField"
                        :sortDirection="$auditSortDirection"
                        label="Action"
                    />
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Payload</th>
                    <th class="rounded-r-xl px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody
                class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
            >
                @forelse ($activityLogs as $log)
                <tr
                    class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                >
                    <!-- prettier-ignore -->
                    <td class="text-light-muted px-4 py-3 font-medium whitespace-nowrap dark:text-gray-400">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <!-- prettier-ignore -->
                    <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                        {{ $log->username }}
                    </td>
                    <td class="px-4 py-3">
                        @php $roleBg = ''; if ($log->role === 'owner') { $roleBg
                        = 'bg-red-500/10 text-red-500'; } elseif ($log->role ===
                        'manager') { $roleBg = 'bg-blue-500/10 text-blue-500'; }
                        elseif ($log->role === 'cashier') { $roleBg =
                        'bg-emerald-500/10 text-emerald-500'; } elseif
                        ($log->role === 'designer') { $roleBg =
                        'bg-purple-500/10 text-purple-500'; } elseif ($log->role
                        === 'production') { $roleBg = 'bg-amber-500/10
                        text-amber-500'; } @endphp
                        <!-- prettier-ignore -->
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $roleBg }}">
                            {{ $log->role }}
                        </span>
                    </td>
                    <!-- prettier-ignore -->
                    <td class="text-brand-secondary px-4 py-3 font-bold uppercase">
                        {{ $log->action }}
                    </td>
                    <!-- prettier-ignore -->
                    <td class="text-light-text px-4 py-3 font-medium dark:text-gray-300">
                        {{ $log->description }}
                    </td>
                    <!-- prettier-ignore -->
                    <td class="text-light-muted max-w-xs truncate px-4 py-3 dark:text-gray-400" title="{{ json_encode($log->payload) }}">
                        @if ($log->payload)
                        <code class="bg-light-surface-2 dark:bg-surface-500 text-light-text border-light-border rounded border px-1.5 py-0.5 text-[10px] dark:border-transparent dark:text-gray-300">
                            {{ json_encode($log->payload) }}
                        </code>
                        @else - @endif
                    </td>
                    <!-- prettier-ignore -->
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button wire:click="openAuditDetail('{{ $log->id }}')" class="bg-brand-primary/10 hover:bg-brand-primary/20 text-brand-primary rounded px-2.5 py-1 font-bold transition-colors duration-150">
                            Detail
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td
                        colspan="7"
                        class="text-light-muted py-8 text-center dark:text-gray-500"
                    >
                        No activity logs recorded.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activityLogs->links() }}</div>
</div>
