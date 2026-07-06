<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Attribute CRUD form -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 h-fit rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            {{ $attributeForm->attributeId ? 'Edit Attribute' : 'Create New
            Attribute' }}
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Manage display, finishing, and cutting parameters.
        </p>

        <form wire:submit.prevent="saveAttribute" class="space-y-4 text-xs">
            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Attribute Name
                </label>
                <input
                    type="text"
                    wire:model="attributeForm.attributeName"
                    placeholder="e.g. Glossy Lamination, Ring Eyelets"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    required
                />
                @error('attributeForm.attributeName')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Attribute Type
                </label>
                <select
                    wire:model="attributeForm.attributeType"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none"
                    required
                >
                    @foreach ($attributeTypesList as $type)
                    <option value="{{ $type->name }}">
                        {{ ucfirst($type->name) }}
                    </option>
                    @endforeach
                </select>
                @error('attributeForm.attributeType')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div class="flex gap-2 pt-2">
                <button
                    type="submit"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary flex-1 cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98] data-loading:cursor-not-allowed data-loading:opacity-50"
                    data-loading-target="saveAttribute"
                >
                    {{ $attributeForm->attributeId ? 'Update Attribute' :
                    'Create Attribute' }}
                </button>
                @if ($attributeForm->attributeId)
                <button
                    type="button"
                    wire:click="resetAttributeForm"
                    class="dark:bg-surface-500 dark:hover:bg-surface-400 rounded-xl bg-gray-100 px-3.5 py-2.5 text-[11px] font-bold text-gray-700 transition-all duration-200 hover:bg-gray-200 dark:text-gray-200"
                >
                    Cancel
                </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Attributes listing -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Display & Finishing Master Data
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            All registered system attributes for order customization.
        </p>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-455 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Attribute Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($allAttributes as $attr)
                    <!-- prettier-ignore -->
                    <tr class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150">
                        <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                            {{ $attr->name }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($attr->type === 'finishing')
                            <span class="rounded bg-blue-500/10 px-2 py-0.5 text-[10px] font-bold text-blue-600 uppercase dark:text-blue-400">
                                Finishing
                            </span>
                            @elseif ($attr->type === 'cut')
                            <span class="rounded bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-600 uppercase dark:text-amber-400">
                                Cut
                            </span>
                            @elseif ($attr->type === 'display')
                            <span class="rounded bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-600 uppercase dark:text-purple-400">
                                Display
                            </span>
                            @else
                            <span class="rounded bg-teal-500/10 px-2 py-0.5 text-[10px] font-bold text-teal-600 uppercase dark:text-teal-400">
                                {{ $attr->type }}
                            </span>
                            @endif
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right font-bold">
                            <button
                                wire:click="editAttribute('{{ $attr->id }}')"
                                class="text-brand-primary hover:text-brand-primary/80 transition"
                            >
                                Edit
                            </button>
                            <button
                                onclick="confirm('Are you sure you want to delete this attribute?') || event.stopImmediatePropagation()"
                                wire:click="deleteAttribute('{{ $attr->id }}')"
                                class="hover:text-red-650 text-red-500 transition"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="3"
                            class="text-light-muted py-6 text-center dark:text-gray-500"
                        >
                            No display/finishing attributes registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
