<div>
    <div class="mb-4 text-sm leading-relaxed text-gray-400">
        {{ __('This is a secure area of the application. Please confirm your
        password before continuing.') }}
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                wire:model="password"
                id="password"
                class="mt-1.5 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button> {{ __('Confirm') }} </x-primary-button>
        </div>
    </form>
</div>
