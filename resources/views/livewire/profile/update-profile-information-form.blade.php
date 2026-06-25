

<section>
    <header>
        <h2 class="text-lg font-semibold text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-400">
            @if (in_array(auth()->user()->role, ['owner', 'manager']))
                {{ __("Update your account's profile information and email address.") }}
            @else
                {{ __("View your account's profile information.") }}
            @endif
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full !bg-surface-600/30 !text-gray-500 cursor-not-allowed !border-surface-300/30" value="{{ auth()->user()->username }}" disabled />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full !disabled:bg-surface-600/30 !disabled:text-gray-500 !disabled:border-surface-300/30" required autofocus autocomplete="name" :disabled="!in_array(auth()->user()->role, ['owner', 'manager'])" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full !disabled:bg-surface-600/30 !disabled:text-gray-500 !disabled:border-surface-300/30" required autocomplete="username" :disabled="!in_array(auth()->user()->role, ['owner', 'manager'])" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-300">
                        {{ __('Your email address is unverified.') }}

                        @if (in_array(auth()->user()->role, ['owner', 'manager']))
                            <button wire:click.prevent="sendVerification" class="underline text-sm text-alja-cyan hover:text-alja-cyan-light rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-alja-magenta/50 focus:ring-offset-surface-700">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        @endif
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
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
