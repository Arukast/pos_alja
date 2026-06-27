<section class="space-y-6">
    <header>
        <h2 class="text-light-text text-lg font-semibold dark:text-white">
            {{ __('Delete Account') }}
        </h2>

        <p class="text-light-muted mt-1 text-sm dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data
            will be permanently deleted. Before deleting your account, please
            download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete Account') }}</x-danger-button
    >

    <x-modal
        name="confirm-user-deletion"
        :show="$errors->isNotEmpty()"
        focusable
    >
        <form
            wire:submit="deleteUser"
            class="text-light-text dark:text-gray-250 p-6"
        >
            <h2 class="text-light-text text-lg font-semibold dark:text-white">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="text-light-muted mt-1 text-sm dark:text-gray-400">
                {{ __('Once your account is deleted, all of its resources and
                data will be permanently deleted. Please enter your password to
                confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label
                    for="password"
                    value="{{ __('Password') }}"
                    class="sr-only"
                />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error
                    :messages="$errors->get('password')"
                    class="mt-2"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-5 py-2.5 text-xs font-semibold tracking-widest uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    {{ __('Cancel') }}
                </button>

                <x-danger-button class="ms-0">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
