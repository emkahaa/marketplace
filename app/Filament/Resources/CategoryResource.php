<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Collection; // Pastikan ini diimpor

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Fieldset;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

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
     * Mendefinisikan skema tabel untuk menampilkan daftar kategori.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                        return ['style' => 'padding-left: ' . ($indentation * 20) . 'px;'];
                    }),

                IconColumn::make('is_prohibit')
                    ->label('Prohibited?')
                    ->boolean(),

                TextColumn::make('permit_status')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active?')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Filter by Parent')
                    ->options(Category::whereNull('parent_id')->pluck('display_name', 'id'))
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn(Builder $query, $value): Builder => $query->where('parent_id', $value),
                    )),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All Categories'),
                Tables\Filters\TernaryFilter::make('is_prohibit')
                    ->label('Prohibited Status')
                    ->trueLabel('Prohibited')
                    ->falseLabel('Not Prohibited')
                    ->placeholder('All Categories'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Tables\Grouping\Group::make('parent_id')
                    ->getTitleFromRecordUsing(function (?Category $record) {
                        if ($record && $record->parent) {
                            return $record->parent->display_name;
                        }
                        return 'Root Categories';
                    })
                    ->collapsible(),
            ])
            ->defaultGroup('parent_id');
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
     * Mengatur query Eloquent dasar untuk resource ini.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'parent.parent', 'parent.parent.parent'])
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END ASC, parent_id ASC, display_name ASC');
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
