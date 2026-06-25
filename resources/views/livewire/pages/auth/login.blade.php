<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-6">
        <!-- Username -->
        <div>
            <label
                for="username"
                class="mb-2 block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                >Username</label
            >
            <div class="relative">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                >
                    <svg
                        class="h-5 w-5 text-gray-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                </div>
                <input
                    wire:model="form.username"
                    id="username"
                    type="text"
                    name="username"
                    required
                    autofocus
                    autocomplete="username"
                    class="bg-surface-600/50 border-surface-300/60 focus:ring-brand-primary/40 focus:border-brand-primary block w-full rounded-xl border py-3 pr-4 pl-11 text-white placeholder-gray-500 transition duration-200 focus:ring-2 focus:outline-none"
                    placeholder="Enter your username"
                />
            </div>
            <x-input-error
                :messages="$errors->get('form.username')"
                class="mt-1"
            />
        </div>

        <!-- Password -->
        <div>
            <label
                for="password"
                class="mb-2 block text-xs font-semibold tracking-wider text-gray-400 uppercase"
                >Password</label
            >
            <div class="relative">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                >
                    <svg
                        class="h-5 w-5 text-gray-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <input
                    wire:model="form.password"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="bg-surface-600/50 border-surface-300/60 focus:ring-brand-primary/40 focus:border-brand-primary block w-full rounded-xl border py-3 pr-4 pl-11 text-white placeholder-gray-500 transition duration-200 focus:ring-2 focus:outline-none"
                    placeholder="••••••••"
                />
            </div>
            <x-input-error
                :messages="$errors->get('form.password')"
                class="mt-1"
            />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label
                for="remember"
                class="inline-flex cursor-pointer items-center select-none"
            >
                <input
                    wire:model="form.remember"
                    id="remember"
                    type="checkbox"
                    class="border-surface-300 bg-surface-600/50 text-brand-primary focus:ring-brand-primary/40 focus:ring-offset-surface-700 h-4 w-4 rounded"
                    name="remember"
                />
                <span class="ms-2 text-sm text-gray-400"
                    >{{ __('Remember me') }}</span
                >
            </label>

            @if (Route::has('password.request'))
            <a
                class="text-brand-secondary hover:text-brand-secondary-light text-sm transition duration-150"
                href="{{ route('password.request') }}"
                wire:navigate
            >
                {{ __('Forgot password?') }}
            </a>
            @endif
        </div>

        <!-- Login Button -->
        <div>
            <button
                type="submit"
                class="bg-brand-primary hover:bg-brand-primary-hover focus:ring-offset-surface-700 focus:ring-brand-primary/50 shadow-glow-primary flex w-full justify-center rounded-xl border border-transparent px-4 py-3.5 text-sm font-bold tracking-wider text-white uppercase transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:outline-none active:scale-[0.98]"
            >
                {{ __('Sign In') }}
            </button>
        </div>
    </form>
</div>
