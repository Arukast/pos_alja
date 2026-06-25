@props([ 'badge' => '', 'title' => '', 'description' => '', 'theme' => 'owner',
// 'owner', 'manager', 'cashier', 'designer', 'production' ]) @php $gradients =
[ 'owner' => 'from-brand-primary/10 to-brand-primary/5 border
border-brand-primary/20 text-gray-900 dark:text-white dark:border-none
dark:from-brand-primary dark:via-[#B0185F] dark:to-surface-700', 'manager' =>
'from-brand-secondary/15 to-brand-secondary/5 border border-brand-secondary/20
text-gray-900 dark:text-white dark:border-none dark:from-brand-secondary
dark:via-[#0088BB] dark:to-surface-700', 'cashier' => 'from-cyan-100 to-blue-50
border border-cyan-200 text-gray-900 dark:text-white dark:border-none
dark:from-cyan-600 dark:to-blue-700', 'designer' => 'from-teal-100 to-emerald-50
border border-teal-200 text-gray-900 dark:text-white dark:border-none
dark:from-teal-600 dark:to-emerald-700', 'production' =>
'from-brand-secondary/15 to-brand-secondary/5 border border-brand-secondary/20
text-gray-900 dark:text-white dark:border-none dark:from-brand-secondary
dark:via-[#0088BB] dark:to-surface-700', ]; $badgeBackgrounds = [ 'owner' =>
'bg-brand-primary/10 text-brand-primary dark:bg-white/15 dark:text-white',
'manager' => 'bg-brand-secondary/10 text-brand-secondary dark:bg-white/15
dark:text-white', 'cashier' => 'bg-cyan-200 text-cyan-800 dark:bg-white/20
dark:text-white', 'designer' => 'bg-teal-200 text-teal-800 dark:bg-white/20
dark:text-white', 'production' => 'bg-brand-secondary/10 text-brand-secondary
dark:bg-white/20 dark:text-white', ]; $descriptionTextColors = [ 'owner' =>
'text-gray-600 dark:text-white/70', 'manager' => 'text-gray-600
dark:text-white/70', 'cashier' => 'text-blue-800/80 dark:text-cyan-100',
'designer' => 'text-emerald-800/80 dark:text-white/70', 'production' =>
'text-gray-600 dark:text-white/70', ]; $gradientClass = $gradients[$theme] ??
$gradients['owner']; $badgeBg = $badgeBackgrounds[$theme] ??
$badgeBackgrounds['owner']; $descTextColor = $descriptionTextColors[$theme] ??
'text-gray-600 dark:text-white/70'; @endphp

<div
    class="relative overflow-hidden rounded-2xl bg-gradient-to-r {{ $gradientClass }} p-6 shadow-md dark:shadow-lg"
>
    <div
        class="relative z-10 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
    >
        <div>
            <div class="mb-2 flex items-center gap-3">
                @if ($badge)
                <span
                    class="inline-block px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $badgeBg }} rounded-full backdrop-blur-sm"
                >
                    {{ $badge }}
                </span>
                @endif @if ($theme === 'owner')
                <div class="flex gap-1">
                    <div
                        class="bg-brand-primary/40 h-2 w-2 rounded-sm dark:bg-white/80"
                    ></div>
                    <div class="bg-brand-secondary h-2 w-2 rounded-sm"></div>
                    <div class="bg-brand-accent h-2 w-2 rounded-sm"></div>
                </div>
                @elseif ($theme === 'manager')
                <div class="flex gap-1">
                    <div class="bg-brand-primary h-2 w-2 rounded-sm"></div>
                    <div
                        class="bg-brand-secondary/40 h-2 w-2 rounded-sm dark:bg-white/80"
                    ></div>
                    <div class="bg-brand-accent h-2 w-2 rounded-sm"></div>
                </div>
                @endif
            </div>

            <h1 class="text-3xl font-extrabold tracking-tight">{{ $title }}</h1>

            @if ($description)
            <p
                class="mt-1 {{ $descTextColor }} max-w-xl text-sm font-medium dark:font-normal"
            >
                {{ $description }}
            </p>
            @endif
        </div>

        {{ $slot }}
    </div>

    <!-- Centralized glow details on the banner -->
    <div
        class="bg-brand-primary/10 absolute -right-10 -bottom-10 h-40 w-40 rounded-full blur-2xl"
    ></div>
    @if ($theme === 'manager')
    <div
        class="bg-brand-accent/10 absolute -top-8 -left-8 h-32 w-32 rounded-full blur-2xl"
    ></div>
    @endif
</div>
