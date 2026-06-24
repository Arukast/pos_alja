<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-6">
        <!-- Username -->
        <div>
            <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Username</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input wire:model="form.username" id="username" type="text" name="username" required autofocus autocomplete="username" 
                    class="block w-full pl-11 pr-4 py-3 bg-surface-600/50 border border-surface-300/60 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-alja-magenta/40 focus:border-alja-magenta transition duration-200"
                    placeholder="Enter your username" />
            </div>
            <x-input-error :messages="$errors->get('form.username')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password"
                    class="block w-full pl-11 pr-4 py-3 bg-surface-600/50 border border-surface-300/60 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-alja-magenta/40 focus:border-alja-magenta transition duration-200"
                    placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" 
                    class="w-4 h-4 rounded border-surface-300 bg-surface-600/50 text-alja-magenta focus:ring-alja-magenta/40 focus:ring-offset-surface-700" name="remember">
                <span class="ms-2 text-sm text-gray-400">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-alja-cyan hover:text-alja-cyan-light transition duration-150" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div>
            <button type="submit" 
                class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-bold text-white bg-alja-magenta hover:bg-alja-magenta-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface-700 focus:ring-alja-magenta/50 shadow-glow-magenta active:scale-[0.98] transition-all duration-200 uppercase tracking-wider">
                {{ __('Sign In') }}
            </button>
        </div>
    </form>
</div>
