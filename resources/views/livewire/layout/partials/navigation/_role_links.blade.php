@props(['role', 'mobile' => false]) @if ($role === 'owner')
<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Overview
    </div>
    <x-navigation.link
        label="Dashboard"
        route="dashboard"
        icon="dashboard"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Insights
    </div>
    <x-navigation.link
        label="Gross Revenue Detail"
        route="dashboard"
        tab="revenue"
        icon="payments"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Base Cost Detail"
        route="dashboard"
        tab="cost"
        icon="ledgers"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Net Profit Detail"
        route="dashboard"
        tab="profit"
        icon="ledgers"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Expenses Detail"
        route="dashboard"
        tab="expenses"
        icon="expenses"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Audit Trail"
        route="dashboard"
        tab="audit"
        icon="audit"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Administration
    </div>
    <x-navigation.link
        label="User Management"
        route="dashboard"
        tab="users"
        icon="users"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Branch Management"
        route="dashboard"
        tab="branches"
        icon="branches"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Product Categories"
        route="dashboard"
        tab="categories"
        icon="materials"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Printer Machines"
        route="dashboard"
        tab="machines"
        icon="print_queue"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Materials & Stock"
        route="dashboard"
        tab="materials"
        icon="materials"
        :mobile="$mobile"
    />
</div>

@elseif ($role === 'manager')
<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Overview
    </div>
    <x-navigation.link
        label="Dashboard"
        route="dashboard"
        icon="dashboard"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Designer Leaderboard"
        route="dashboard"
        tab="leaderboard"
        icon="leaderboard"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Inventory
    </div>
    <x-navigation.link
        label="Materials & Stock"
        route="dashboard"
        tab="materials"
        icon="materials"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Stock Ledgers"
        route="dashboard"
        tab="ledgers"
        icon="ledgers"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Configuration
    </div>
    <x-navigation.link
        label="Display & Finishing"
        route="dashboard"
        tab="finishing"
        icon="finishing"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Product Categories"
        route="dashboard"
        tab="categories"
        icon="materials"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Printer Machines"
        route="dashboard"
        tab="machines"
        icon="print_queue"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Pricing Matrices"
        route="dashboard"
        tab="pricing"
        icon="pricing"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Administration
    </div>
    <x-navigation.link
        label="User Management"
        route="dashboard"
        tab="users"
        icon="users"
        :mobile="$mobile"
    />
</div>

@elseif ($role === 'cashier')
<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Overview
    </div>
    <x-navigation.link
        label="Dashboard"
        route="dashboard"
        icon="dashboard"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Operations
    </div>
    <x-navigation.link
        label="Payment Queue"
        route="dashboard"
        tab="payments"
        icon="payments"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Pickup Queue"
        route="dashboard"
        tab="pickup"
        icon="pickup"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Expenses"
        route="dashboard"
        tab="expenses"
        icon="expenses"
        :mobile="$mobile"
    />
</div>

@elseif ($role === 'designer')
<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Overview
    </div>
    <x-navigation.link
        label="Dashboard"
        route="dashboard"
        tab="dashboard"
        icon="dashboard"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Orders
    </div>
    <x-navigation.link
        label="New Order"
        route="dashboard"
        tab="new_order"
        icon="new_order"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Order List"
        route="dashboard"
        tab="order_list"
        icon="order_list"
        :mobile="$mobile"
    />
</div>

@elseif ($role === 'production')
<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Overview
    </div>
    <x-navigation.link
        label="Dashboard"
        route="dashboard"
        icon="dashboard"
        :mobile="$mobile"
    />
</div>

<div class="space-y-1">
    <div
        @if(!$mobile)
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        @endif
        class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
    >
        Operations
    </div>
    <x-navigation.link
        label="Print Queue"
        route="dashboard"
        tab="print_queue"
        icon="print_queue"
        :mobile="$mobile"
    />
    <x-navigation.link
        label="Waste Logging"
        route="dashboard"
        tab="waste"
        icon="waste"
        :mobile="$mobile"
    />
</div>
@endif
