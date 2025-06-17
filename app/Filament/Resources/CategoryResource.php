<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category; // Memastikan model Category diimpor
use Filament\Forms;      // Mengimpor namespace Forms
use Filament\Forms\Form; // Mengimpor kelas Form
use Filament\Resources\Resource; // Mengimpor kelas Resource
use Filament\Tables;     // Mengimpor namespace Tables
use Filament\Tables\Table; // Mengimpor kelas Table
use Illuminate\Database\Eloquent\Builder; // Mengimpor kelas Builder untuk query Eloquent
use Illuminate\Database\Eloquent\SoftDeletingScope; // Mengimpor SoftDeletingScope (meskipun mungkin tidak langsung digunakan di sini)
use Illuminate\Support\Str; // Mengimpor kelas Str untuk helper string seperti slug

// Mengimpor komponen Forms yang lebih spesifik untuk kejelasan
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue; // Digunakan untuk region_setting
use Filament\Forms\Components\Fieldset; // Digunakan untuk mengelompokkan input region_setting

// Mengimpor komponen Tables yang lebih spesifik
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class CategoryResource extends Resource
{
    // Mendefinisikan model yang digunakan oleh resource ini
    protected static ?string $model = Category::class;

    // Menentukan ikon navigasi untuk resource ini di sidebar Filament
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Mendefinisikan grup navigasi di sidebar (opsional, untuk kerapian)
    protected static ?string $navigationGroup = 'Shop Management';

    // Mendefinisikan urutan navigasi dalam grup (opsional)
    protected static ?int $navigationSort = 1;

    /**
     * Mendefinisikan skema form untuk membuat dan mengedit kategori.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Input untuk nama kategori
                TextInput::make('name')
                    ->required() // Kolom wajib diisi
                    ->maxLength(255) // Batas maksimal karakter
                    ->live(onBlur: true) // Mengaktifkan Livewire untuk memperbarui slug saat nama selesai diinput
                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                    ->helperText('Nama internal kategori, misal: "Women Clothes".') // Informasi bantuan untuk user
                    ->columnSpan(1), // Mengatur lebar kolom di form

                // Input untuk slug kategori (otomatis dari nama, tapi bisa diedit manual)
                TextInput::make('slug')
                    ->required() // Kolom wajib diisi
                    ->unique(ignoreRecord: true, table: Category::class) // Harus unik, abaikan record saat ini jika sedang edit
                    ->maxLength(255) // Batas maksimal karakter
                    ->helperText('URL yang mudah dibaca, otomatis terisi dari nama, misal: "women-clothes".') // Informasi bantuan
                    ->columnSpan(1), // Mengatur lebar kolom di form

                // Input untuk nama tampilan kategori
                TextInput::make('display_name')
                    ->required() // Kolom wajib diisi
                    ->maxLength(255) // Batas maksimal karakter
                    ->helperText('Nama yang akan ditampilkan ke pembeli, misal: "Pakaian Wanita".') // Informasi bantuan
                    ->columnSpan('full'), // Mengatur lebar kolom penuh di form

                // Dropdown untuk memilih kategori induk (parent)
                Select::make('parent_id')
                    ->label('Parent Category') // Label input
                    ->options(
                        Category::pluck('display_name', 'id') // Mengambil semua kategori sebagai pilihan
                            ->prepend('No Parent', null) // Menambahkan opsi 'No Parent' (null)
                    )
                    ->searchable() // Mengaktifkan fitur pencarian dalam dropdown
                    ->placeholder('Select a parent category') // Placeholder
                    ->helperText('Pilih kategori induk jika ini adalah sub-kategori. Kosongkan jika kategori utama.') // Informasi bantuan
                    ->nullable() // Memungkinkan kolom ini kosong (untuk kategori root)
                    ->columnSpan('full'), // Mengatur lebar kolom penuh

                // Input untuk pengaturan wilayah (region_setting)
                Fieldset::make('Region Settings') // Mengelompokkan input terkait pengaturan wilayah
                    ->schema([
                        Toggle::make('region_setting.enable_size_chart')
                            ->label('Enable Size Chart')
                            ->helperText('Aktifkan jika kategori ini memerlukan panduan ukuran (misal: Pakaian).')
                            ->default(false), // Default sesuai data contoh

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
                    ->columns(2) // Mengatur tata letak kolom di dalam fieldset
                    ->columnSpan('full'), // Fieldset mengisi lebar penuh form


                // Toggle untuk status 'is_prohibit' (dari JSON asli)
                Toggle::make('is_prohibit')
                    ->label('Is Prohibited?') // Label input
                    ->helperText('Tandai jika kategori ini dilarang dan tidak boleh tampil. Contoh: Barang Ilegal.') // Informasi bantuan
                    ->default(false) // Nilai default FALSE
                    ->required(), // Kolom wajib diisi (opsional, tergantung kebutuhan bisnis)

                // Input untuk status izin (permit_status)
                TextInput::make('permit_status')
                    ->label('Permit Status') // Label input
                    ->required() // Kolom wajib diisi
                    ->numeric() // Hanya menerima input angka
                    ->default(0) // Nilai default 0
                    ->helperText('Status izin untuk kategori, nilai 0 (default) atau lainnya.') // Informasi bantuan
                    ->columnSpan('full'), // Mengatur lebar kolom penuh

                // Toggle untuk status 'is_active' (kontrol visibilitas umum)
                Toggle::make('is_active')
                    ->label('Is Active?') // Label input
                    ->helperText('Kontrol visibilitas kategori di tampilan publik (frontend).') // Informasi bantuan
                    ->default(true) // NILAI DEFAULT TRUE sesuai permintaan
                    ->required(), // Kolom wajib diisi

            ])->columns(2); // Mengatur tata letak kolom utama form menjadi 2 kolom
    }

    /**
     * Mendefinisikan skema tabel untuk menampilkan daftar kategori.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom display_name akan menjadi kolom utama yang menampilkan struktur pohon
                TextColumn::make('display_name')
                    ->searchable()
                    ->sortable()
                    ->label('Category Name')
                    ->extraAttributes(function (Category $record) {
                        $indentation = 0;
                        $parent = $record->parent;
                        while ($parent) {
                            $indentation++;
                            $parent = $parent->parent;
                        }
                        // Menambahkan padding kiri berdasarkan level hirarki
                        return ['style' => 'padding-left: ' . ($indentation * 20) . 'px;'];
                    }),

                // Kolom icon untuk is_prohibit
                IconColumn::make('is_prohibit')
                    ->label('Prohibited?') // Label kolom
                    ->boolean(), // Menampilkan sebagai icon boolean

                // Kolom permit_status
                TextColumn::make('permit_status')
                    ->numeric() // Menampilkan sebagai angka
                    ->sortable(), // Dapat diurutkan

                // Kolom icon untuk is_active
                IconColumn::make('is_active')
                    ->label('Active?') // Label kolom
                    ->boolean(), // Menampilkan sebagai icon boolean

                // Kolom created_at
                TextColumn::make('created_at')
                    ->dateTime() // Menampilkan sebagai tanggal dan waktu
                    ->sortable() // Dapat diurutkan
                    ->toggleable(isToggledHiddenByDefault: true), // Dapat disembunyikan secara default

                // Kolom updated_at
                TextColumn::make('updated_at')
                    ->dateTime() // Menampilkan sebagai tanggal dan waktu
                    ->sortable() // Dapat diurutkan
                    ->toggleable(isToggledHiddenByDefault: true), // Dapat disembunyikan secara default

                // Kolom deleted_at (untuk soft deletes)
                TextColumn::make('deleted_at')
                    ->dateTime() // Menampilkan sebagai tanggal dan waktu
                    ->sortable() // Dapat diurutkan
                    ->toggleable(isToggledHiddenByDefault: true), // Dapat disembunyikan secara default
            ])
            ->filters([
                // Filter untuk kategori induk (hanya menampilkan kategori root sebagai pilihan filter)
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Filter by Parent')
                    ->options(Category::whereNull('parent_id')->pluck('display_name', 'id'))
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn(Builder $query, $value): Builder => $query->where('parent_id', $value),
                    )),
                // Filter ternary untuk status aktif
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All Categories'),
                // Filter ternary untuk status dilarang
                Tables\Filters\TernaryFilter::make('is_prohibit')
                    ->label('Prohibited Status')
                    ->trueLabel('Prohibited')
                    ->falseLabel('Not Prohibited')
                    ->placeholder('All Categories'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Aksi edit untuk setiap baris
                Tables\Actions\DeleteAction::make(), // Aksi delete (akan melakukan soft delete)
                Tables\Actions\RestoreAction::make(), // Aksi restore (jika item di-soft delete)
                Tables\Actions\ForceDeleteAction::make(), // Aksi force delete (menghapus permanen)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Aksi bulk delete (soft delete)
                    Tables\Actions\RestoreBulkAction::make(), // Aksi bulk restore
                    Tables\Actions\ForceDeleteBulkAction::make(), // Aksi bulk force delete
                ]),
            ])
            // Tambahkan pengelompokan berdasarkan parent_id
            // ->groupRecordsBy('parent_id')
            // Mengatur label grup
            ->groups([
                Tables\Grouping\Group::make('parent_id')
                    ->getTitleFromRecordUsing(function (?Category $record) {
                        if ($record && $record->parent) {
                            return $record->parent->display_name;
                        }
                        return 'Root Categories'; // Untuk kategori tanpa parent
                    })
                    ->collapsible(), // Membuat grup bisa dilipat
            ])
            ->defaultGroup('parent_id'); // Mengatur pengelompokan default
    }

    /**
     * Mendefinisikan relasi yang akan ditampilkan di halaman detail/edit resource.
     */
    public static function getRelations(): array
    {
        return [
            // RelationManagers::make('ChildrenRelationManager', Category::class) // Contoh jika ingin mengelola anak dari halaman detail parent
            // Untuk audit log, gunakan AuditRelationManager jika telah dibuat
            // RelationManagers\AuditsRelationManager::class, // Memastikan AuditRelationManager diimpor
        ];
    }

    /**
     * Mendefinisikan halaman-halaman yang tersedia untuk resource ini.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),    // Halaman daftar kategori
            'create' => Pages\CreateCategory::route('/create'), // Halaman buat kategori baru
            'edit' => Pages\EditCategory::route('/{record}/edit'), // Halaman edit kategori
        ];
    }

    /**
     * Mengatur query Eloquent dasar untuk resource ini.
     */
    public static function getEloquentQuery(): Builder
    {
        // Menggunakan withTrashed() untuk menampilkan semua kategori (aktif, tidak aktif, soft deleted)
        // Jika hanya ingin menampilkan yang tidak di-soft delete, hapus ->withTrashed()
        return parent::getEloquentQuery()
            // Penting: urutkan agar kategori induk muncul sebelum anaknya
            ->with('parent')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END, parent_id, name');
    }
}
