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
            <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Username</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input wire:model="form.username" id="username" type="text" name="username" required autofocus autocomplete="username" 
                    class="block w-full pl-10 pr-4 py-3 bg-[#0B0F19]/50 border border-slate-800/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-600/50 focus:border-violet-500 transition duration-200"
                    placeholder="Enter your username" />
            </div>
            <x-input-error :messages="$errors->get('form.username')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password"
                    class="block w-full pl-10 pr-4 py-3 bg-[#0B0F19]/50 border border-slate-800/80 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-600/50 focus:border-violet-500 transition duration-200"
                    placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" 
                    class="w-4.5 h-4.5 rounded border-slate-800 bg-[#0B0F19]/50 text-violet-600 focus:ring-violet-500/50 focus:ring-offset-[#131825]" name="remember">
                <span class="ms-2 text-sm text-slate-400">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-violet-400 hover:text-violet-300 transition duration-150" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <div>
            <button type="submit" 
                class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#131825] focus:ring-violet-500 shadow-lg shadow-indigo-500/20 active:scale-[0.98] transition-all duration-150">
                {{ __('Sign In') }}
            </button>
        </div>
    </form>
</div>
