<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/categories.json'); // Pastikan categories.json ada di sini

        if (!File::exists($jsonPath)) {
            $this->command->error("File categories.json not found at: {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        // Digunakan untuk melacak ID terbesar dari JSON
        $maxIdFromJson = 0;

        // Nonaktifkan pemeriksaan foreign key sementara untuk seeding.
        // Ini penting karena relasi self-referencing dan ID diatur manual.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate(); // Membersihkan tabel sebelum seeding jika ada data lama

        $this->command->info('Seeding categories with IDs and parent IDs from JSON...');
        $this->insertCategoriesRecursively($data['data']['list'], $maxIdFromJson);

        // Setelah semua data JSON diimpor, atur ulang AUTO_INCREMENT
        // agar penambahan data selanjutnya melanjutkan dari ID terbesar yang ada.
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = ' . ($maxIdFromJson + 1) . ';');

        // Aktifkan kembali pemeriksaan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Opsional: Perbarui flag has_children dan has_active_children
        $this->command->info('Updating has_children flags...');
        $this->updateCategoryChildrenFlags();

        $this->command->info('Category seeding completed!');
    }

    /**
     * Rekursif untuk memasukkan kategori dengan ID dan parent_id langsung dari JSON.
     * ID parent harus sudah ada di database saat anak dibuat,
     * jadi kita akan mengandalkan urutan impor atau fakta bahwa JSON sudah terstruktur baik.
     */
    private function insertCategoriesRecursively(
        array $categoriesData,
        int &$maxIdFromJson
    ): void {
        foreach ($categoriesData as $categoryData) {
            // Pastikan slug unik
            $slug = Str::slug($categoryData['name']);
            $originalSlug = $slug;
            $counter = 1;
            // Loop ini untuk memastikan slug unik jika ada duplikasi nama
            while (Category::where('slug', $slug)->exists() && $slug !== null) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Langsung gunakan ID dari JSON sebagai ID PRIMARY KEY di database
            // parent_id juga langsung dari JSON, asumsikan parent sudah ada atau null
            $category = Category::create([
                'id'                  => $categoryData['id'],
                'name'                => $categoryData['name'],
                'display_name'        => $categoryData['display_name'],
                'slug'                => $slug,
                // Jika parent_id dari JSON adalah 0, berarti ini root.
                // Jika bukan 0, gunakan parent_id dari JSON.
                'parent_id'           => ($categoryData['parent_id'] === 0) ? null : $categoryData['parent_id'],
                'is_active'           => true, // Default aktif untuk kategori yang diimpor
                'has_active_children' => $categoryData['has_active_children'] ?? false,
                'has_children'        => $categoryData['has_children'] ?? false,
                'region_setting'      => $categoryData['region_setting'] ?? null,
                'is_prohibit'         => $categoryData['is_prohibit'] ?? false,
                'permit_status'       => $categoryData['permit_status'] ?? 0,
            ]);

            // Melacak ID terbesar dari JSON
            $maxIdFromJson = max($maxIdFromJson, $categoryData['id']);

            // Proses anak-anak jika ada
            if (isset($categoryData['children']) && count($categoryData['children']) > 0) {
                $this->insertCategoriesRecursively(
                    $categoryData['children'],
                    $maxIdFromJson // Max ID terus dilacak di semua level rekursi
                );
            }
        }
    }

    /**
     * Memperbarui flag has_children dan has_active_children untuk semua kategori.
     */
    private function updateCategoryChildrenFlags(): void
    {
        // Ambil semua kategori
        $allCategories = Category::all();

        foreach ($allCategories as $category) {
            // Hitung ulang has_children
            $category->has_children = $category->children()->exists();

            // Hitung ulang has_active_children (asumsi is_prohibit=false berarti aktif)
            // Atau Anda bisa menggunakan is_active dan is_prohibit sesuai logika bisnis Anda
            $category->has_active_children = $category->children()->where('is_active', true)->exists();

            // Simpan perubahan
            $category->save();
        }
    }
}
