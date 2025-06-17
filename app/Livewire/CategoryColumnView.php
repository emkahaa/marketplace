<?php

namespace App\Livewire;

use Filament\Forms\Set;
use Livewire\Component;
use App\Models\Category;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\Action; // Import Action jika ingin menggunakan di Livewire
use Filament\Forms\Concerns\InteractsWithForms; // Untuk menggunakan form di Livewire
use Filament\Tables\Concerns\InteractsWithTable; // Untuk menggunakan tabel di Livewire

class CategoryColumnView extends Component implements HasForms // Tambahkan HasForms
{
    use InteractsWithForms; // Trait untuk form

    public $selectedCategoryL1 = null; // ID kategori yang dipilih di kolom 1
    public $selectedCategoryL2 = null; // ID kategori yang dipilih di kolom 2
    public $selectedCategoryL3 = null; // ID kategori yang dipilih di kolom 3
    // Tambahkan lebih banyak sesuai kedalaman hirarki Anda

    public $editingCategory = null; // ID kategori yang sedang diedit/ditambah
    public $showCreateForm = false;
    public $showEditForm = false;
    public $formState = []; // State untuk form Filament

    // Properti untuk menyimpan data kategori di setiap level
    public $categoriesL1;
    public $categoriesL2;
    public $categoriesL3;

    // Metode mount akan dipanggil saat komponen diinisialisasi
    public function mount()
    {
        $this->loadCategoriesL1();
    }

    // Metode untuk memuat kategori level 1 (root)
    public function loadCategoriesL1()
    {
        $this->categoriesL1 = Category::root()->orderBy('name')->get();
        $this->selectedCategoryL1 = null;
        $this->selectedCategoryL2 = null;
        $this->selectedCategoryL3 = null;
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->editingCategory = null;
    }

    // Metode untuk memilih kategori di kolom 1
    public function selectCategoryL1($categoryId)
    {
        $this->selectedCategoryL1 = $categoryId;
        $this->selectedCategoryL2 = null;
        $this->selectedCategoryL3 = null;
        $this->categoriesL2 = Category::where('parent_id', $categoryId)->orderBy('name')->get();
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->editingCategory = null;
    }

    // Metode untuk memilih kategori di kolom 2
    public function selectCategoryL2($categoryId)
    {
        $this->selectedCategoryL2 = $categoryId;
        $this->selectedCategoryL3 = null;
        $this->categoriesL3 = Category::where('parent_id', $categoryId)->orderBy('name')->get();
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->editingCategory = null;
    }

    // Metode untuk memilih kategori di kolom 3 (lanjutkan jika ada level lebih dalam)
    public function selectCategoryL3($categoryId)
    {
        $this->selectedCategoryL3 = $categoryId;
        // Jika ada L4, muat di sini: $this->categoriesL4 = Category::where('parent_id', $categoryId)->orderBy('name')->get();
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->editingCategory = null;
    }

    // Aksi untuk membuka form tambah kategori
    public function openCreateForm(?int $parentId = null)
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->editingCategory = null;
        // Set nilai default parent_id di form jika menambah anak
        $this->form->fill([
            'parent_id' => $parentId,
            'is_active' => true // Default is_active true
        ]);
    }

    // Aksi untuk membuka form edit kategori
    public function openEditForm($categoryId)
    {
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->editingCategory = Category::find($categoryId);
        $this->form->fill($this->editingCategory->toArray());
    }

    // Skema form untuk tambah/edit kategori (mirip dengan CategoryResource::form)
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn(string $operation, $state, Set $set) => $set('slug', Str::slug($state)))
                ->helperText('Nama internal kategori, misal: "Women Clothes".')
                ->columnSpan(1),

            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: $this->editingCategory, table: Category::class) // Pastikan unik, abaikan jika sedang edit
                ->maxLength(255)
                ->helperText('URL yang mudah dibaca, otomatis terisi dari nama, misal: "women-clothes".')
                ->columnSpan(1),

            TextInput::make('display_name')
                ->required()
                ->maxLength(255)
                ->helperText('Nama yang akan ditampilkan ke pembeli, misal: "Pakaian Wanita".')
                ->columnSpan('full'),

            Select::make('parent_id')
                ->label('Parent Category')
                ->options(
                    // Exclude self and children from parent options
                    static::getCategoryOptionsForFormLivewire($this->editingCategory ? $this->editingCategory->id : null)
                        ->prepend('No Parent', null)
                )
                ->searchable()
                ->placeholder('Select a parent category')
                ->helperText('Pilih kategori induk jika ini adalah sub-kategori. Kosongkan jika kategori utama.')
                ->nullable()
                ->columnSpan('full'),

            Fieldset::make('Region Settings')
                ->schema([
                    Toggle::make('region_setting.enable_size_chart')
                        ->label('Enable Size Chart')
                        ->helperText('Aktifkan jika kategori ini memerlukan panduan ukuran (misal: Pakaian).')
                        ->default(false),
                    TextInput::make('region_setting.low_stock_value')
                        ->label('Low Stock Value')
                        ->numeric()
                        ->default(0)
                        ->helperText('Nilai batas stok rendah untuk kategori ini.')
                        ->columnSpan(1),
                    Toggle::make('region_setting.dimension_mandatory')
                        ->label('Dimension Mandatory')
                        ->helperText('Wajibkan pengisian dimensi produk untuk kategori ini.')
                        ->default(false),
                    Toggle::make('region_setting.is_fashion_category')
                        ->label('Is Fashion Category')
                        ->helperText('Tandai jika kategori ini termasuk dalam fashion.')
                        ->default(false),
                    Toggle::make('region_setting.enable_compatibility')
                        ->label('Enable Compatibility')
                        ->helperText('Aktifkan fitur kompatibilitas produk (misal: untuk sparepart kendaraan).')
                        ->default(false),
                    Toggle::make('region_setting.is_local_block')
                        ->label('Is Local Block')
                        ->helperText('Tandai jika kategori ini diblokir untuk penjualan lokal.')
                        ->default(false),
                    Toggle::make('region_setting.is_cb_block')
                        ->label('Is Cross-Border Block')
                        ->helperText('Tandai jika kategori ini diblokir untuk penjualan lintas batas (cross-border).')
                        ->default(false),
                    TextInput::make('region_setting.gtin_validation_rule')
                        ->label('GTIN Validation Rule')
                        ->numeric()
                        ->default(0)
                        ->helperText('Aturan validasi GTIN (Global Trade Item Number) untuk kategori ini.')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpan('full'),

            Toggle::make('is_prohibit')
                ->label('Is Prohibited?')
                ->helperText('Tandai jika kategori ini dilarang dan tidak boleh tampil. Contoh: Barang Ilegal.')
                ->default(false)
                ->required(),

            TextInput::make('permit_status')
                ->label('Permit Status')
                ->required()
                ->numeric()
                ->default(0)
                ->helperText('Status izin untuk kategori, nilai 0 (default) atau lainnya.')
                ->columnSpan('full'),

            Toggle::make('is_active')
                ->label('Is Active?')
                ->helperText('Kontrol visibilitas kategori di tampilan publik (frontend).')
                ->default(true)
                ->required(),
        ];
    }

    // Inisialisasi form Filament
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->model(Category::class) // Model default untuk form
            ->statePath('formState'); // State path untuk data form
    }

    // Metode untuk menyimpan kategori baru
    public function createCategory()
    {
        try {
            $data = $this->form->getState();
            // ID akan otomatis digenerate karena AUTO_INCREMENT telah diatur di seeder
            $category = Category::create($data);

            Notification::make()
                ->title('Category created successfully!')
                ->success()
                ->send();

            $this->loadCategoriesBasedOnSelection(); // Refresh tampilan kolom
            $this->resetForm(); // Reset form
            $this->showCreateForm = false; // Tutup form
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating category')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Metode untuk mengupdate kategori
    public function updateCategory()
    {
        try {
            $data = $this->form->getState();
            $this->editingCategory->update($data);

            Notification::make()
                ->title('Category updated successfully!')
                ->success()
                ->send();

            $this->loadCategoriesBasedOnSelection(); // Refresh tampilan kolom
            $this->resetForm(); // Reset form
            $this->showEditForm = false; // Tutup form
            $this->editingCategory = null;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error updating category')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Metode untuk menghapus kategori (soft delete)
    public function deleteCategory($categoryId)
    {
        try {
            $category = Category::find($categoryId);
            if ($category) {
                $category->delete(); // Soft delete
                Notification::make()
                    ->title('Category deleted successfully!')
                    ->success()
                    ->send();
                $this->loadCategoriesBasedOnSelection(); // Refresh tampilan kolom
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting category')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Metode untuk memuat kategori di setiap kolom berdasarkan seleksi saat ini
    public function loadCategoriesBasedOnSelection()
    {
        $this->categoriesL1 = Category::root()->orderBy('name')->get();

        if ($this->selectedCategoryL1) {
            $this->categoriesL2 = Category::where('parent_id', $this->selectedCategoryL1)->orderBy('name')->get();
        } else {
            $this->categoriesL2 = null;
        }

        if ($this->selectedCategoryL2) {
            $this->categoriesL3 = Category::where('parent_id', $this->selectedCategoryL2)->orderBy('name')->get();
        } else {
            $this->categoriesL3 = null;
        }
        // Lanjutkan untuk level yang lebih dalam
    }

    // Reset form
    protected function resetForm()
    {
        $this->form->fill(); // Mengosongkan form
    }


    // --- Metode Bantuan untuk Dropdown Form (copy dari CategoryResource) ---
    protected static function getCategoryOptionsForFormLivewire(?int $currentCategoryId = null): Collection
    {
        $query = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name');

        // Jika sedang mengedit kategori, kecualikan kategori itu sendiri dan semua anak-anaknya
        if ($currentCategoryId) {
            $excludedIds = collect([$currentCategoryId]);
            $excludedIds = $excludedIds->merge(static::getAllChildIdsRecursiveLivewire(Category::find($currentCategoryId)));
            $query->whereNotIn('id', $excludedIds->toArray());
        }

        $categories = $query->get();
        $options = collect();
        static::buildCategoryOptionsLivewire($categories, $options);
        return $options;
    }

    protected static function buildCategoryOptionsLivewire(Collection $categories, Collection &$options, $level = 0, $prefix = ''): void
    {
        foreach ($categories as $category) {
            $options->put($category->id, $prefix . $category->display_name);

            if ($category->children->isNotEmpty()) {
                static::buildCategoryOptionsLivewire(
                    $category->children->sortBy('name'),
                    $options,
                    $level + 1,
                    $prefix . '-- '
                );
            }
        }
    }

    protected static function getAllChildIdsRecursiveLivewire(Category $category): Collection
    {
        $childIds = collect();
        foreach ($category->children as $child) {
            $childIds->push($child->id);
            $childIds = $childIds->merge(static::getAllChildIdsRecursiveLivewire($child));
        }
        return $childIds->unique();
    }


    // Render tampilan
    public function render()
    {
        return view('livewire.category-column-view');
    }
}
