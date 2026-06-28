{{-- ADD/EDIT USER MODAL --}} @if ($showUserModal)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs"
    >
        <x-brand-stripe />

        {{-- Modal Header --}}
        <div
            class="border-light-border dark:border-surface-300/10 mb-4 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    {{ $userIdBeingEdited ? 'Edit User Account' : 'Add User
                    Account' }}
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    Configure user settings, role classification, and branch
                    scoping.
                </p>
            </div>
            <button
                @click="$wire.set('showUserModal', false)"
                class="text-light-muted hover:text-light-text dark:text-gray-450 transition-colors dark:hover:text-gray-300"
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
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
            </button>
        </div>

        {{-- Modal Form --}}
        <form wire:submit.prevent="saveUser" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Full Name
                        <span class="font-bold text-red-500">*</span></label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formName"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formName')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Username
                        <span class="font-bold text-red-500">*</span></label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formUsername"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formUsername')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Email Address
                        <span class="font-bold text-red-500">*</span></label
                    >
                    <x-text-input
                        type="email"
                        wire:model="formEmail"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('formEmail')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Staff Role
                        <span class="font-bold text-red-500">*</span></label
                    >
                    <select
                        wire:model="formRole"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:text-gray-100"
                    >
                        @if ($mode === 'owner' &&
                        $this->isPrimaryOwner(auth()->user()))
                        <option
                            value="owner"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Owner (owner)
                        </option>
                        @endif @if ($mode === 'owner')
                        <option
                            value="manager"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Manager (manager)
                        </option>
                        @endif
                        <option
                            value="cashier"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Cashier (cashier)
                        </option>
                        <option
                            value="designer"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Designer (designer)
                        </option>
                        <option
                            value="production"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Production (production)
                        </option>
                    </select>
                    @error('formRole')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Password
                        @if(!$userIdBeingEdited)
                            <span class="font-bold text-red-500">*</span>
                        @endif
                        {{ $userIdBeingEdited ? '(Leave blank to keep current)' : '' }}
                    </label>
                    @if ($userIdBeingEdited)
                    <x-text-input
                        type="password"
                        wire:model="formPassword"
                        class="w-full !py-2 !text-xs"
                    />
                    @else
                    <x-text-input
                        type="password"
                        wire:model="formPassword"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @endif @error('formPassword')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Phone Number</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formPhone"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('formPhone')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @if ($mode === 'owner')
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Assign Branch
                        @if($formRole !== 'owner')
                            <span class="font-bold text-red-500">*</span>
                        @endif
                    </label>
                    <select
                        wire:model="formBranchId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:text-gray-100"
                    >
                        <option
                            value=""
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            Global (No Branch)
                        </option>
                        @foreach ($allBranches as $branch)
                        <option
                            value="{{ $branch->id }}"
                            class="dark:bg-surface-500 bg-white text-gray-900 dark:text-gray-100"
                        >
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('formBranchId')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                @endif
                <div class="{{ $mode === 'owner' ? '' : 'col-span-2' }}">
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Address</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="formAddress"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('formAddress')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                <button
                    type="button"
                    @click="$wire.set('showUserModal', false)"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <x-primary-button
                    type="submit"
                    class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                >
                    Save Staff
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endif
