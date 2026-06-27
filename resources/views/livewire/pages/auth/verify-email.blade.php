<div>
    <div class="mb-4 text-sm leading-relaxed text-gray-400">
        {{ __('Thanks for signing up! Before getting started, could you verify
        your email address by clicking on the link we just emailed to you? If
        you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
    <div class="mb-4 text-sm font-medium text-emerald-400">
        {{ __('A new verification link has been sent to the email address you
        provided during registration.') }}
    </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend Verification Email') }}
        </x-primary-button>

        <button
            wire:click="logout"
            type="submit"
            class="text-brand-secondary hover:text-brand-secondary-light cursor-pointer text-sm transition duration-150"
        >
            {{ __('Log Out') }}
        </button>
    </div>
</div>
