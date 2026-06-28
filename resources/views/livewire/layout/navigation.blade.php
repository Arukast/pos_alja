@php $role = auth()->user()->role; $initials = collect(explode(' ',
auth()->user()->name))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
@endphp

<div class="flex h-full shrink-0">
    <!-- ========================================== -->
    <!-- 1. DESKTOP SIDEBAR                         -->
    <!-- ========================================== -->
    @include('livewire.layout.partials.navigation._desktop_sidebar', ['role' =>
    $role, 'initials' => $initials])

    <!-- ========================================== -->
    <!-- 2. MOBILE DRAWER NAVIGATION                -->
    <!-- ========================================== -->
    @include('livewire.layout.partials.navigation._mobile_drawer', ['role' =>
    $role, 'initials' => $initials])
</div>
