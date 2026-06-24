<nav class="-mx-3 flex flex-1 justify-end">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none focus-visible:ring-alja-magenta"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none focus-visible:ring-alja-magenta"
        >
            Log in
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-xl px-4 py-2 text-white ring-1 ring-transparent transition hover:text-white/80 focus:outline-none focus-visible:ring-alja-magenta"
            >
                Register
            </a>
        @endif
    @endauth
</nav>
