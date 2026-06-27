<x-app-layout>
    <x-slot name="header">
        <h2
            class="text-xl leading-tight font-bold text-gray-800 dark:text-white"
        >
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-[1600px] space-y-6 sm:px-6 lg:px-8">
            <div
                class="dark:bg-surface-600 border-light-border dark:border-surface-300/40 dark:shadow-card rounded-2xl border bg-white p-4 shadow-sm sm:p-8"
            >
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div
                class="dark:bg-surface-600 border-light-border dark:border-surface-300/40 dark:shadow-card rounded-2xl border bg-white p-4 shadow-sm sm:p-8"
            >
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            @if (in_array(auth()->user()->role, ['owner', 'manager']))
            <div
                class="dark:bg-surface-600 border-light-border dark:border-surface-300/40 dark:shadow-card rounded-2xl border bg-white p-4 shadow-sm sm:p-8"
            >
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
