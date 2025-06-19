<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Category;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Http\UploadedFile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

use Filament\Forms\Components\Fieldset;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CategoryResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Shop Management';

    protected static ?int $navigationSort = 1;

    /**
     * Mendefinisikan skema form untuk membuat dan mengedit kategori.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                    ->helperText('Nama internal kategori, misal: "Women Clothes".')
                    ->columnSpan(1),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true, table: Category::class)
                    ->maxLength(255)
                    ->helperText('URL yang mudah dibaca, otomatis terisi dari nama, misal: "women-clothes".')
                    ->columnSpan(1),

                FileUpload::make('image') // <-- Tambahkan ini
                    ->label('Category Image')
                    ->image() // Hanya menerima file gambar
                    ->directory('category-images') // Folder penyimpanan di storage/app/public/
                    ->disk('public') // Gunakan disk 'public'
                    ->nullable() // Gambar tidak wajib
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file): string => (string) Str::random(40) . '.' . $file->getClientOriginalExtension(),
                    )
                    ->helperText('Unggah gambar untuk kategori. Maksimal ukuran 2MB.'),

                TextInput::make('display_name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Nama yang akan ditampilkan ke pembeli, misal: "Pakaian Wanita".')
                    ->columnSpan('full'),

                // Dropdown untuk memilih kategori induk (parent)
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->options(function () { // Menggunakan closure untuk opsi
                        $options = static::getHierarchicalCategoryOptions(); // Ambil opsi hierarkis

                        $currentRecordId = null;
                        if ($record = request()->route('record')) {
                            if (is_object($record) && method_exists($record, 'getKey')) {
                                $currentRecordId = $record->getKey();
                            } else {
                                $currentRecordId = $record;
                            }
                        }

                        if ($currentRecordId) {
                            // Hapus kategori yang sedang diedit dari opsi parent
                            unset($options[$currentRecordId]);

                            // Opsional: Jika Anda juga ingin menghapus SEMUA keturunan dari record ini
                            // untuk mencegah loop tak terbatas, Anda perlu fungsi untuk mendapatkan semua ID anak.
                            // Contoh sederhana (tidak rekursif):
                            // $children = Category::where('parent_id', $currentRecordId)->pluck('id')->toArray();
                            // foreach ($children as $childId) {
                            //     unset($options[$childId]);
                            // }
                            // Untuk solusi rekursif yang lebih kompleks, kita bisa bahas terpisah.
                        }

                        // Tambahkan opsi 'No Parent' di awal
                        return collect($options)->prepend('No Parent', null)->all();
                    })
                    ->searchable() // Mengaktifkan fitur pencarian dalam dropdown
                    ->placeholder('Select a parent category') // Placeholder
                    ->helperText('Pilih kategori induk jika ini adalah sub-kategori. Kosongkan jika kategori utama.')
                    ->nullable() // Memungkinkan kolom ini kosong (untuk kategori root)
                    ->columnSpan('full'), // Mengatur lebar kolom penuh

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
                            ->columnSpan(1)
                            ->helperText('Nilai batas stok rendah untuk kategori ini.'),

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
                            ->columnSpan(1)
                            ->helperText('Aturan validasi GTIN (Global Trade Item Number) untuk kategori ini.'),
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
            ])->columns(2);
    }

    /**
     * Mendefinisikan relasi yang akan ditampilkan di halaman detail/edit resource.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Mendefinisikan halaman-halaman yang tersedia untuk resource ini.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryTree::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    /**
     * Helper method to generate hierarchical category options for Select fields.
     */
    protected static function getHierarchicalCategoryOptions(): array
    {
        // Eager load all necessary parent levels to prevent N+1 queries during path building.
        // Adjust the depth (e.g., .parent.parent.parent.parent) based on your maximum expected hierarchy.
        $categories = Category::with('parent', 'parent.parent', 'parent.parent.parent', 'parent.parent.parent.parent')->get();
        $options = [];

        foreach ($categories as $category) {
            $path = [$category->display_name]; // Mulai dengan nama kategori itu sendiri
            $current = $category;

            // Traverse ke atas ke parent root
            // Gunakan `relationLoaded('parent')` untuk memastikan relasi sudah di-eager load
            // dan tidak memicu kueri N+1
            while ($current->relationLoaded('parent') && $current->parent) {
                array_unshift($path, $current->parent->display_name); // Tambahkan nama parent ke awal
                $current = $current->parent;
            }

            $options[$category->id] = implode(' > ', $path);
        }

        // Urutkan opsi secara alfabetis berdasarkan jalur tampilan mereka
        asort($options);

        return $options;
    }
}
