<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryTree extends Component
{
    // Ini sekarang akan menjadi Collection dari objek PHP biasa (stdClass atau array),
    // BUKAN lagi objek model Eloquent langsung.
    public Collection $categories;
    public array $expandedNodes = [];
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->loadCategories();
    }

    public function mount(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $query = Category::query();

        // Pilih hanya kolom-kolom yang Anda butuhkan.
        // Ini akan mengambil data sebagai objek PHP dasar atau array, bukan model Eloquent penuh.
        $query->select('id', 'name', 'display_name', 'parent_id', 'is_prohibit', 'permit_status', 'is_active');


        if ($this->search) {
            $query->where(function ($q) {
                $q->where('display_name', 'like', '%' . $this->search . '%')
                    ->orWhere('name', 'like', '%' . $this->search . '%');
            });
        }

        // 1. Ambil SEMUA data kategori yang relevan secara datar.
        // Gunakan ->get()->map()->keyBy() untuk memastikan kita bekerja dengan objek PHP biasa.
        // Ini akan menjalankan SATU kueri SQL.
        $allCategoriesRaw = $query->get()->map(function ($category) {
            // Konversi model Eloquent menjadi objek PHP standar (stdClass)
            // Ini akan membuat 'children' menjadi properti biasa, bukan relasi Eloquent
            return (object) $category->toArray();
        })->keyBy('id');


        // Inisialisasi expanded nodes dan jalur pencarian jika diperlukan
        if ($this->search) {
            $this->expandedNodes = [];
            foreach ($allCategoriesRaw as $category) {
                // Pastikan akses properti menggunakan -> karena ini sekarang stdClass object
                if (Str::contains($category->display_name, $this->search, true) || Str::contains($category->name, $this->search, true)) {
                    $this->expandPathToNode($allCategoriesRaw, $category->id);
                }
            }
        }

        // 2. Bangun struktur pohon di memori PHP dari data mentah ini.
        $tree = []; // Untuk menyimpan kategori root
        $tempChildrenCollections = []; // Koleksi sementara untuk membangun anak-anak

        // Inisialisasi koleksi anak sementara untuk setiap kategori
        foreach ($allCategoriesRaw as $category) {
            $tempChildrenCollections[$category->id] = collect();
            if (!isset($this->expandedNodes[$category->id])) {
                $this->expandedNodes[$category->id] = false;
            }
        }

        // Distribusi kategori ke koleksi anak-anak parent mereka
        foreach ($allCategoriesRaw as $category) {
            if ($category->parent_id === null || !$allCategoriesRaw->has($category->parent_id)) {
                // Ini adalah kategori root, atau parent-nya tidak ada dalam set data yang difilter
                $tree[] = $category;
            } else {
                // Ini adalah anak, tambahkan ke koleksi anak sementara parent-nya
                $tempChildrenCollections[$category->parent_id]->push($category);
            }
        }

        // 3. Tetapkan koleksi anak yang telah dibangun kembali ke objek kategori (sebagai properti biasa)
        foreach ($allCategoriesRaw as $category) {
            $childrenCollection = $tempChildrenCollections[$category->id]->sortBy('display_name')->values();
            // Tetapkan koleksi anak langsung sebagai properti 'children'.
            // Karena ini adalah objek PHP biasa, tidak ada relasi Eloquent yang akan dipicu.
            $category->children = $childrenCollection;
        }

        // 4. Urutkan kategori root dan tetapkan ke properti publik
        $this->categories = collect($tree)->sortBy('display_name')->values();
    }

    // expandPathToNode akan bekerja dengan objek PHP biasa
    protected function expandPathToNode(Collection $allCategoriesRaw, $nodeId)
    {
        $category = $allCategoriesRaw->get($nodeId);
        // Mengakses parent_id langsung dari objek
        while ($category && $category->parent_id !== null) {
            $this->expandedNodes[$category->parent_id] = true;
            $category = $allCategoriesRaw->get($category->parent_id);
        }
    }

    public function toggleNode(int $categoryId): void
    {
        $this->expandedNodes[$categoryId] = !$this->expandedNodes[$categoryId];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.category-tree');
    }
}
