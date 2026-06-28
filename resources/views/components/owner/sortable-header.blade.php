@props([ 'field', 'sortField', 'sortDirection', 'label', 'class' => '' ])

<th
    class="hover:text-brand-primary cursor-pointer px-4 py-3 transition-colors duration-150 select-none {{ $class }}"
    wire:click="sortByAudit('{{ $field }}')"
>
    <!-- prettier-ignore -->
    <div class="flex items-center space-x-1">
        <span>{{ $label ?? $slot }}</span>
        <span class="text-[10px] {{ $sortField === $field ? 'text-brand-primary font-bold' : 'text-gray-400 dark:text-gray-600' }}">
            {{ $sortField === $field ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}
        </span>
    </div>
</th>
