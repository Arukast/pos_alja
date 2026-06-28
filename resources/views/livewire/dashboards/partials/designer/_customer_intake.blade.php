<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-5 shadow-sm"
>
    <h2 class="text-light-text mb-4 text-lg font-bold dark:text-white">
        1. Customer Intake
    </h2>

    @if (!$showNewCustomerForm)
    <!-- Search Field -->
    <div class="space-y-3">
        <label
            class="text-light-muted block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
            >Search Customer</label
        >
        <div class="relative">
            <input
                id="customer-search"
                type="text"
                wire:model.live.debounce.300ms="customerForm.search"
                placeholder="Type name or phone..."
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white py-2.5 pr-10 pl-3 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                data-loading:class="opacity-50"
            />
            @if ($customerId)
            <div
                class="absolute top-3 right-3 text-emerald-500 dark:text-emerald-400"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </div>
            @endif
        </div>

        <!-- Dropdown Results -->
        @if ($customerForm->search && !$customerId)
        <div
            class="border-light-border dark:bg-surface-600 divide-light-border dark:divide-surface-300/30 max-h-40 divide-y overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-gray-700/50"
        >
            @forelse ($customers as $c)
            <!-- prettier-ignore -->
            <button
                            id="customer-option-{{ $c->id }}"
                            type="button"
                            wire:click="selectCustomer('{{ $c->id }}')"
                            class="text-light-text hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 w-full cursor-pointer px-3 py-2 text-left text-xs font-bold transition dark:text-gray-200"
                            data-loading:attr="disabled"
                        >
                            {{ $c->name }} ({{ $c->phone ?: 'No Phone' }}) -
                            <span class="text-brand-secondary font-bold">{{ $c->type }}</span>
                        </button>
            @empty
            <div
                class="text-light-muted px-3 py-2.5 text-center text-xs font-medium dark:text-gray-500"
            >
                No customer found.
            </div>
            @endforelse
        </div>
        @endif

        <button
            id="btn-create-customer-mode"
            type="button"
            wire:click="$set('showNewCustomerForm', true)"
            class="border-light-border text-brand-secondary hover:bg-light-surface-2 dark:hover:bg-surface-500/50 w-full cursor-pointer rounded-xl border border-dashed py-2.5 text-xs font-bold transition dark:border-gray-700/50"
        >
            + Create New Customer
        </button>
    </div>
    @else
    <!-- New Customer Form -->
    <form wire:submit.prevent="createCustomer" class="space-y-3">
        <div class="flex items-center justify-between">
            <h3
                class="text-light-muted text-xs font-bold uppercase dark:text-gray-400"
            >
                New Customer
            </h3>
            <button
                id="btn-cancel-create-customer"
                type="button"
                wire:click="$set('showNewCustomerForm', false)"
                class="text-red-550 cursor-pointer text-xs font-bold dark:text-red-400"
            >
                Cancel
            </button>
        </div>

        <input
            id="new-customer-name"
            type="text"
            wire:model="customerForm.name"
            placeholder="Full Name *"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            required
        />
        <input
            id="new-customer-phone"
            type="text"
            wire:model="customerForm.phone"
            placeholder="Phone Number"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
        />
        <input
            id="new-customer-email"
            type="email"
            wire:model="customerForm.email"
            placeholder="Email"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
        />
        <textarea
            id="new-customer-address"
            wire:model="customerForm.address"
            placeholder="Address"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
        ></textarea>

        <select
            id="new-customer-type"
            wire:model="customerForm.type"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none"
        >
            <option value="Reguler" class="bg-white text-gray-900">
                Reguler
            </option>
            <option value="Mitra" class="bg-white text-gray-900">Mitra</option>
        </select>

        <button
            id="btn-submit-new-customer"
            type="submit"
            class="bg-brand-secondary hover:bg-brand-secondary/95 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl py-2 text-xs font-bold text-white transition"
            data-loading:class="opacity-50 pointer-events-none"
        >
            <span
                data-loading:inline-block
                class="hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
            ></span>
            Save & Select Customer
        </button>
    </form>
    @endif @error('customerId')
    <p class="text-red-550 mt-2 text-xs font-bold dark:text-red-400">
        {{ $message }}
    </p>
    @enderror
</div>
