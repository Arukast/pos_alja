<section>
    <header>
        <h2 class="text-light-text text-lg font-semibold dark:text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="text-light-muted mt-1 text-sm dark:text-gray-400">
            {{ in_array(auth()->user()->role, ['owner', 'manager']) ? __("Update
            your account's profile information and email address.") : __("View
            your account's profile information.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input
                id="username"
                name="username"
                type="text"
                class="!bg-light-surface-2 dark:!bg-surface-600/30 !border-light-border dark:!border-surface-300/30 !text-light-muted mt-1 block w-full cursor-not-allowed dark:!text-gray-500"
                value="{{ auth()->user()->username }}"
                disabled
            />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input
                wire:model="name"
                id="name"
                name="name"
                type="text"
                class="disabled:!bg-light-surface-2 dark:disabled:!bg-surface-600/30 disabled:!text-light-muted disabled:!border-light-border dark:disabled:!border-surface-300/30 mt-1 block w-full dark:disabled:!text-gray-500"
                required
                autofocus
                autocomplete="name"
                :disabled="!in_array(auth()->user()->role, ['owner', 'manager'])"
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                wire:model="email"
                id="email"
                name="email"
                type="email"
                class="disabled:!bg-light-surface-2 dark:disabled:!bg-surface-600/30 disabled:!text-light-muted disabled:!border-light-border dark:disabled:!border-surface-300/30 mt-1 block w-full dark:disabled:!text-gray-500"
                required
                autocomplete="username"
                :disabled="!in_array(auth()->user()->role, ['owner', 'manager'])"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof
            \Illuminate\Contracts\Auth\MustVerifyEmail && !
            auth()->user()->hasVerifiedEmail())
            <div>
                <!-- prettier-ignore -->
                <p class="mt-2 text-sm text-light-text dark:text-gray-300">
                    {{ __('Your email address is unverified.') }}
                    @if (in_array(auth()->user()->role, ['owner', 'manager']))
                    <button
                        wire:click.prevent="sendVerification"
                        class="text-brand-secondary hover:text-brand-secondary-light focus:ring-brand-primary/50 focus:ring-offset-light-bg dark:focus:ring-offset-surface-700 rounded-md text-sm underline focus:ring-2 focus:ring-offset-2 focus:outline-none"
                    >
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                    @endif
                </p>

                @if (session('status') === 'verification-link-sent')
                <p
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    {{ __('A new verification link has been sent to your email
                    address.') }}
                </p>
                @endif
            </div>
            @endif
        </div>

        @if (in_array(auth()->user()->role, ['owner', 'manager']))
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
        @endif
    </form>
</section>
