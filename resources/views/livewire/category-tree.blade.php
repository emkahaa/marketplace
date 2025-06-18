<div>
    {{-- Input Pencarian --}}
    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search categories..."
            class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                      filament-forms-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
    </div>

    {{-- Tabel Kategori --}}
    <table class="filament-tables-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
        <thead>
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-950 dark:text-white">Category Name</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-950 dark:text-white">Prohibited?</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-950 dark:text-white">Permit Status
                </th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-950 dark:text-white">Active?</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-950 dark:text-white">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            {{-- Memulai rendering baris pohon --}}
            @include('livewire.partials.category-node', ['categories' => $categories, 'level' => 0])
        </tbody>
    </table>
</div>
