@props([ 'mode' => 'app', ]) @if ($mode === 'guest')
<!-- Background Glow Blobs — Brand-inspired ambient lighting -->
<div
    class="bg-brand-primary animate-glow-pulse pointer-events-none absolute top-[-15%] left-[-5%] h-[550px] w-[550px] rounded-full opacity-0 blur-[150px] dark:opacity-[0.12]"
></div>
<div
    class="bg-brand-secondary animate-glow-pulse pointer-events-none absolute right-[-5%] bottom-[-15%] h-[500px] w-[500px] rounded-full opacity-0 blur-[150px] dark:opacity-[0.08]"
    style="animation-delay: 1.5s"
></div>
<div
    class="bg-brand-accent pointer-events-none absolute top-[60%] left-[60%] h-[300px] w-[300px] rounded-full opacity-0 blur-[120px] dark:opacity-[0.04]"
></div>
@else
<!-- Subtle ambient glow blobs for premium feel -->
<div
    class="bg-brand-primary pointer-events-none fixed top-0 left-0 h-[500px] w-[500px] rounded-full opacity-0 blur-[120px] dark:opacity-[0.03]"
></div>
<div
    class="bg-brand-secondary pointer-events-none fixed right-0 bottom-0 h-[400px] w-[400px] rounded-full opacity-0 blur-[120px] dark:opacity-[0.03]"
></div>
@endif
