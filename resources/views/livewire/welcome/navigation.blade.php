<nav class="-mx-3 flex flex-1 justify-end">
    @auth
    <a
        href="{{ url('/dashboard') }}"
        class="focus-visible:ring-brand-primary rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none"
    >
        Dashboard
    </a>
    @else
    <a
        href="{{ route('login') }}"
        class="focus-visible:ring-brand-primary rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none"
    >
        Log in
    </a>

    @if (Route::has('register'))
    <a
        href="{{ route('register') }}"
        class="focus-visible:ring-brand-primary rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none"
    >
        Register
    </a>
    @endif @endauth
</nav>
