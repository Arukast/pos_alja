@if ($activeTab === 'waste')
<!-- Log failed prints / quick actions -->
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-1"
>
    <h3 class="text-light-text text-lg font-bold dark:text-white">
        Log Print Waste
    </h3>
    <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
        Report failed runs to adjust inventory.
    </p>

    <form wire:submit.prevent="logWaste" class="space-y-4 text-xs">
        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
            >
                Select Material *
            </label>
            <select
                id="waste-material-id"
                wire:model="form.materialId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                required
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Choose Material --
                </option>
                @foreach ($allMaterials as $m)
                <option
                    value="{{ $m->id }}"
                    class="text-light-text font-semibold dark:text-gray-200"
                >
                    <!-- prettier-ignore -->
                    {{ $m->name }} {{ $m->dimensions ? "({$m->dimensions})" : '' }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
            >
                Printer Machine
            </label>
            <select
                id="waste-machine-id"
                wire:model="form.machineId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Choose Machine --
                </option>
                @foreach ($allMachines as $mac)
                <option
                    value="{{ $mac->id }}"
                    class="text-light-text font-semibold dark:text-gray-200"
                >
                    {{ $mac->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Wasted Qty *
                </label>
                <input
                    id="waste-quantity"
                    type="number"
                    step="any"
                    min="0.01"
                    wire:model="form.quantity"
                    placeholder="e.g. 1.5 or 5"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    required
                />
            </div>
            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Dimensions (m)
                </label>
                <input
                    id="waste-dimensions"
                    type="text"
                    wire:model="form.dimensions"
                    placeholder="e.g. 2x1"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                />
            </div>
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
            >
                Responsible Designer
            </label>
            <select
                id="waste-designer-id"
                wire:model="form.designerId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Choose Designer --
                </option>
                @foreach ($designersList as $des)
                <option
                    value="{{ $des->id }}"
                    class="text-light-text dark:text-gray-200"
                >
                    {{ $des->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
            >
                Failure Reason *
            </label>
            <textarea
                id="waste-notes"
                wire:model="form.notes"
                placeholder="e.g. Paper jam, head clog, color mismatch..."
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                required
            ></textarea>
        </div>

        <button
            id="btn-submit-waste"
            type="submit"
            class="w-full cursor-pointer rounded-xl bg-red-500 py-2.5 font-bold text-white shadow-sm transition hover:bg-red-600 data-loading:cursor-not-allowed data-loading:opacity-50"
        >
            Submit Material Waste Log
        </button>
    </form>
</div>
@endif
