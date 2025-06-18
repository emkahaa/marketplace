@foreach ($categories as $category)
    <tr class="filament-tables-row bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-white/5">
        {{-- Kolom Category Name dengan Indentasi dan Toggle --}}
        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-950 dark:text-white"
            style="padding-left: {{ $level * 20 + 12 }}px;">
            <div class="flex items-center space-x-2">
                {{-- Tombol Expand/Collapse --}}
                @if ($category->children->count() > 0)
                    <button wire:click="toggleNode({{ $category->id }})"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none">
                        @if ($expandedNodes[$category->id] ?? false)
                            {{-- Icon panah ke bawah (Expanded) --}}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        @else
                            {{-- Icon panah ke kanan (Collapsed) --}}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                                </path>
                            </svg>
                        @endif
                    </button>
                @else
                    <div class="w-4 h-4"></div> {{-- Placeholder untuk item tanpa anak agar sejajar --}}
                @endif
                <span class="font-semibold">{{ $category->display_name }}</span>
            </div>
        </td>

        {{-- Kolom Prohibited? --}}
        <td class="whitespace-nowrap px-3 py-4 text-center">
            @if ($category->is_prohibit)
                <x-heroicon-o-check-circle class="h-5 w-5 text-green-500 mx-auto" title="Prohibited" />
            @else
                <x-heroicon-o-x-circle class="h-5 w-5 text-red-500 mx-auto" title="Not Prohibited" />
            @endif
        </td>

        {{-- Kolom Permit Status --}}
        <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $category->permit_status }}
        </td>

        {{-- Kolom Active? --}}
        <td class="whitespace-nowrap px-3 py-4 text-center">
            @if ($category->is_active)
                <x-heroicon-o-check-circle class="h-5 w-5 text-green-500 mx-auto" title="Active" />
            @else
                <x-heroicon-o-x-circle class="h-5 w-5 text-red-500 mx-auto" title="Inactive" />
            @endif
        </td>

        {{-- Kolom Actions (Edit Link) --}}
        <td class="whitespace-nowrap px-3 py-4 text-center text-sm font-medium">
            {{-- PERUBAHAN DI SINI: CAST KE INTEGER --}}
            <a href="{{ \App\Filament\Resources\CategoryResource::getUrl('edit', ['record' => (int) $category->id]) }}"
                class="text-primary-600 hover:text-primary-500 hover:underline">
                Edit
            </a>
        </td>
    </tr>

    {{-- Rekursif untuk anak-anak jika node diperluas --}}
    @if (($expandedNodes[$category->id] ?? false) && $category->children->count() > 0)
        @include('livewire.partials.category-node', [
            'categories' => $category->children,
            'level' => $level + 1,
        ])
    @endif
@endforeach
