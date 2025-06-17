<div class="filament-page filament-resources-list-records-page">
    <div class="fi-header flex flex-col gap-y-6">
        <div class="fi-header-wrapper flex flex-col items-start justify-between gap-y-4 md:flex-row md:items-center">
            <div class="fi-header-heading flex items-center gap-x-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    Manage Categories
                </h1>
            </div>

            <div class="fi-header-actions flex shrink-0 items-center gap-3">
                {{-- Tombol untuk menambah kategori root --}}
                <x-filament::button wire:click="openCreateForm({{ null }})">
                    Add Root Category
                </x-filament::button>
            </div>
        </div>
    </div>

    <div class="fi-page-content">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Kolom 1: Kategori Level 1 (Root) --}}
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold mb-3">Level 1 Categories</h2>
                @forelse ($categoriesL1 as $category)
                    <div class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer
                                {{ $selectedCategoryL1 == $category->id ? 'bg-primary-50 dark:bg-primary-900 ring-2 ring-primary-500' : '' }}"
                        wire:click="selectCategoryL1({{ $category->id }})">
                        <span
                            class="text-sm font-medium {{ $category->is_active ? '' : 'text-gray-500 line-through' }}">
                            {{ $category->display_name }}
                        </span>
                        <div class="flex items-center gap-x-2">
                            @if ($category->has_children)
                                <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400" />
                            @endif
                            <x-filament::dropdown placement="bottom-end">
                                <x-slot:trigger>
                                    <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" label="Actions"
                                        class="-my-1" />
                                </x-slot:trigger>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item wire:click="openEditForm({{ $category->id }})">
                                        Edit
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item wire:click="openCreateForm({{ $category->id }})">
                                        Add Child
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item wire:click="deleteCategory({{ $category->id }})"
                                        color="danger">
                                        Delete
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No categories found.</p>
                @endforelse
            </div>

            {{-- Kolom 2: Kategori Level 2 (Anak dari L1) --}}
            @if ($selectedCategoryL1)
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold mb-3">Level 2 Categories
                        ({{ Category::find($selectedCategoryL1)->display_name ?? 'N/A' }})</h2>
                    @forelse ($categoriesL2 as $category)
                        <div class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer
                                    {{ $selectedCategoryL2 == $category->id ? 'bg-primary-50 dark:bg-primary-900 ring-2 ring-primary-500' : '' }}"
                            wire:click="selectCategoryL2({{ $category->id }})">
                            <span
                                class="text-sm font-medium {{ $category->is_active ? '' : 'text-gray-500 line-through' }}">
                                {{ $category->display_name }}
                            </span>
                            <div class="flex items-center gap-x-2">
                                @if ($category->has_children)
                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400" />
                                @endif
                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot:trigger>
                                        <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" label="Actions"
                                            class="-my-1" />
                                    </x-slot:trigger>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item wire:click="openEditForm({{ $category->id }})">
                                            Edit
                                        </x-filament::dropdown.list.item>
                                        <x-filament::dropdown.list.item
                                            wire:click="openCreateForm({{ $category->id }})">
                                            Add Child
                                        </x-filament::dropdown.list.item>
                                        <x-filament::dropdown.list.item
                                            wire:click="deleteCategory({{ $category->id }})" color="danger">
                                            Delete
                                        </x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No subcategories found.</p>
                    @endforelse
                </div>
            @endif

            {{-- Kolom 3: Kategori Level 3 (Anak dari L2) --}}
            @if ($selectedCategoryL2)
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold mb-3">Level 3 Categories
                        ({{ Category::find($selectedCategoryL2)->display_name ?? 'N/A' }})</h2>
                    @forelse ($categoriesL3 as $category)
                        <div class="flex items-center justify-between p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md cursor-pointer
                                    {{ $selectedCategoryL3 == $category->id ? 'bg-primary-50 dark:bg-primary-900 ring-2 ring-primary-500' : '' }}"
                            wire:click="selectCategoryL3({{ $category->id }})">
                            <span
                                class="text-sm font-medium {{ $category->is_active ? '' : 'text-gray-500 line-through' }}">
                                {{ $category->display_name }}
                            </span>
                            <div class="flex items-center gap-x-2">
                                {{-- Jika ada level lebih dalam, tambahkan icon chevron di sini --}}
                                @if ($category->has_children)
                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400" />
                                @endif
                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot:trigger>
                                        <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" label="Actions"
                                            class="-my-1" />
                                    </x-slot:trigger>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item wire:click="openEditForm({{ $category->id }})">
                                            Edit
                                        </x-filament::dropdown.list.item>
                                        <x-filament::dropdown.list.item
                                            wire:click="openCreateForm({{ $category->id }})">
                                            Add Child
                                        </x-filament::dropdown.list.item>
                                        <x-filament::dropdown.list.item
                                            wire:click="deleteCategory({{ $category->id }})" color="danger">
                                            Delete
                                        </x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No subcategories found.</p>
                    @endforelse
                </div>
            @endif
            {{-- Tambahkan kolom 4, 5, dst. dengan pola yang sama --}}
        </div>
    </div>

    {{-- Form Modal untuk Tambah Kategori --}}
    <x-filament::modal id="create-category-modal" :visible="$showCreateForm" width="3xl">
        <x-slot name="heading">
            Create New Category
        </x-slot>

        <x-filament-forms::form wire:submit="createCategory">
            {{ $this->form }}
        </x-filament-forms::form>

        <x-slot name="footer">
            <x-filament::button wire:click="createCategory">
                Create
            </x-filament::button>
            <x-filament::button color="secondary" wire:click="$set('showCreateForm', false)">
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Form Modal untuk Edit Kategori --}}
    <x-filament::modal id="edit-category-modal" :visible="$showEditForm" width="3xl">
        <x-slot name="heading">
            Edit Category
        </x-slot>

        <x-filament-forms::form wire:submit="updateCategory">
            {{ $this->form }}
        </x-filament-forms::form>

        <x-slot name="footer">
            <x-filament::button wire:click="updateCategory">
                Save Changes
            </x-filament::button>
            <x-filament::button color="secondary" wire:click="$set('showEditForm', false)">
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
