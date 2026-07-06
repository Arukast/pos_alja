<div class="space-y-6">
    <!-- Feedback alert -->
    <x-manager.feedback-alert
        :show="$showSuccessAlert"
        :message="$successMessage"
    />

    <!-- Categories list -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Product Categories
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Manage catalog product categories and configure their
                    allowed attribute constraints.
                </p>
            </div>
            <div>
                <button
                    type="button"
                    wire:click="openCategoryModal"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Register Category
                </button>
            </div>
        </div>

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
                        <th class="rounded-l-xl px-4 py-3">Category Name</th>
                        <th class="px-4 py-3">Calculation Archetype</th>
                        <th class="px-4 py-3">Allowed Attribute Constraints</th>
                        <th class="rounded-r-xl px-4 py-3 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($categoriesList as $category)
                    <tr
                        class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 even:bg-light-bg/50 dark:even:bg-surface-500/20 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $category->name }}
                        </td>
                        <td
                            class="px-4 py-3 font-medium text-gray-500 dark:text-gray-300"
                        >
                            {{ str_replace('_', ' ',
                            $category->calculation_archetype) }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            @if (empty($category->allowed_attribute_types))
                            <span
                                class="rounded-md bg-green-50 px-2 py-1 text-[9px] font-bold text-green-600 dark:bg-green-950/20 dark:text-green-400"
                            >
                                All Types Permitted
                            </span>
                            @else
                            <div class="flex flex-wrap gap-1">
                                @foreach ($category->allowed_attribute_types as
                                $type)
                                <span
                                    class="text-brand-primary dark:text-brand-primary/90 bg-brand-primary/10 rounded-md px-2 py-1 text-[9px] font-bold"
                                >
                                    {{ $type }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    wire:click="editCategory('{{ $category->id }}')"
                                    class="cursor-pointer font-bold text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Edit
                                </button>
                                <span class="text-gray-350 dark:text-gray-600"
                                    >|</span
                                >
                                <button
                                    type="button"
                                    wire:click="deleteCategory('{{ $category->id }}')"
                                    onclick="
                                        confirm(
                                            'Are you sure you want to delete this category? All related products and materials will be deleted.'
                                        ) || event.stopImmediatePropagation()
                                    "
                                    class="cursor-pointer font-bold text-red-600 transition-colors hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="4"
                            class="text-light-muted px-4 py-8 text-center font-semibold dark:text-gray-400"
                        >
                            No categories registered yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attribute Types list -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Attribute Types
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Define dynamic custom attribute categories with dedicated
                    calculation archetypes.
                </p>
            </div>
            <div>
                <button
                    type="button"
                    wire:click="openTypeModal"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Register Attribute Type
                </button>
            </div>
        </div>

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
                        <th class="rounded-l-xl px-4 py-3">Type Name</th>
                        <th class="px-4 py-3">Calculation Archetype</th>
                        <th class="rounded-r-xl px-4 py-3 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($attributeTypesList as $type)
                    <tr
                        class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 even:bg-light-bg/50 dark:even:bg-surface-500/20 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $type->name }}
                        </td>
                        <td
                            class="px-4 py-3 font-medium text-gray-500 dark:text-gray-300"
                        >
                            {{ str_replace('_', ' ',
                            $type->calculation_archetype) }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if (in_array($type->name, ['finishing', 'cut',
                                'display']))
                                <span
                                    class="font-semibold text-gray-400 italic dark:text-gray-600"
                                    >System Locked</span
                                >
                                @else
                                <button
                                    type="button"
                                    wire:click="deleteType('{{ $type->id }}')"
                                    onclick="
                                        confirm(
                                            'Are you sure you want to delete this attribute type? Existing attributes of this type will no longer validate.'
                                        ) || event.stopImmediatePropagation()
                                    "
                                    class="cursor-pointer font-bold text-red-600 transition-colors hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="3"
                            class="text-light-muted px-4 py-8 text-center font-semibold dark:text-gray-400"
                        >
                            No custom attribute types registered yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attribute Type Modal -->
    @if ($isCreatingType)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card relative w-full max-w-md overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs"
        >
            <x-brand-stripe />

            <div
                class="border-light-border dark:border-surface-300/10 mb-4 flex items-center justify-between border-b pb-3"
            >
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Register Attribute Type
                    </h3>
                    <p
                        class="text-light-muted mt-0.5 text-xs dark:text-gray-400"
                    >
                        Create a dynamic custom attribute type.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="closeTypeModal"
                    class="text-light-muted hover:text-light-text dark:text-gray-455 transition-colors dark:hover:text-gray-300"
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

            <form wire:submit.prevent="saveType" class="space-y-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Type Name
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="typeName"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('typeName')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Calculation Archetype
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <select
                        wire:model="typeCalculationArchetype"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none"
                        required
                    >
                        <option value="per_piece">Per Piece</option>
                        <option value="surface_area">Surface Area</option>
                        <option value="linear_dimension">
                            Linear Dimension
                        </option>
                        <option value="tiered_volume">Tiered Volume</option>
                    </select>
                    @error('typeCalculationArchetype')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div
                    class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
                >
                    <button
                        type="button"
                        wire:click="closeTypeModal"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                    >
                        Cancel
                    </button>
                    <x-primary-button
                        type="submit"
                        class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                    >
                        Save Attribute Type
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Category Modal -->
    @if ($isCreatingCategory)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card relative w-full max-w-md overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs"
        >
            <x-brand-stripe />

            {{-- Modal Header --}}
            <div
                class="border-light-border dark:border-surface-300/10 mb-4 flex items-center justify-between border-b pb-3"
            >
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        {{ $editingCategoryId ? 'Edit Category' : 'Register
                        Category' }}
                    </h3>
                    <p
                        class="text-light-muted mt-0.5 text-xs dark:text-gray-400"
                    >
                        {{ $editingCategoryId ? 'Modify category configuration.'
                        : 'Create a new category for products and materials.' }}
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="closeCategoryModal"
                    class="text-light-muted hover:text-light-text dark:text-gray-455 transition-colors dark:hover:text-gray-300"
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

            <form wire:submit.prevent="saveCategory" class="space-y-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Category Name
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="categoryForm.name"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('categoryForm.name')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Calculation Archetype
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <select
                        wire:model="categoryForm.calculation_archetype"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none"
                        required
                    >
                        <option value="per_piece">Per Piece</option>
                        <option value="surface_area">Surface Area</option>
                        <option value="linear_dimension">
                            Linear Dimension
                        </option>
                        <option value="tiered_volume">Tiered Volume</option>
                    </select>
                    @error('categoryForm.calculation_archetype')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Allowed Attribute Constraints
                    </label>
                    <div class="mt-2 space-y-2">
                        @foreach ($attributeTypesList as $attrType)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                value="{{ $attrType->name }}"
                                wire:model="categoryForm.allowed_attribute_types"
                                class="text-brand-primary focus:ring-brand-primary/30 rounded border-gray-300"
                            />
                            <span
                                class="text-light-text font-semibold dark:text-gray-300"
                                >{{ ucfirst($attrType->name) }}</span
                            >
                        </label>
                        @endforeach
                    </div>
                    <p
                        class="text-light-muted mt-1 text-[10px] dark:text-gray-400"
                    >
                        Select which types of attributes are valid. Leave all
                        unchecked to permit any.
                    </p>
                    @error('categoryForm.allowed_attribute_types')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div
                    class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
                >
                    <button
                        type="button"
                        wire:click="closeCategoryModal"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                    >
                        Cancel
                    </button>
                    <x-primary-button
                        type="submit"
                        class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                    >
                        Save Category
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
