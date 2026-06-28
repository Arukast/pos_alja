@props(['mobile' => false])

<div
    @if($mobile)
    x-show="mobilePopoverOpen"
    @else
    x-show="popoverOpen"
    @endif
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
    class="border-light-border bg-light-surface dark:border-surface-300/40 dark:bg-surface-700 absolute right-3 bottom-[100%] left-3 z-50 mb-2 rounded-xl border p-2 shadow-lg"
    style="bottom: 100%; display: none"
>
    <div class="space-y-1">
        <a
            href="{{ route('profile') }}"
            wire:navigate
            @if($mobile)
            @click="mobileOpen = false"
            @endif
            class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition dark:text-gray-300"
        >
            <x-icon name="profile" class="h-4 w-4 shrink-0 text-current" />
            <span>Profile & Settings</span>
        </a>
        <div
            class="border-light-border dark:border-surface-300/20 my-1 border-t"
        ></div>
        <button
            wire:click="logout"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs font-semibold text-red-500 transition hover:bg-red-500/10 dark:hover:bg-red-500/20"
        >
            <x-icon name="logout" class="h-4 w-4 shrink-0 text-current" />
            <span>Log Out</span>
        </button>
    </div>
</div>
