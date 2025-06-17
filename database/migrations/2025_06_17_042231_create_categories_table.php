<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // Perubahan dari bigIncrements menjadi unsignedBigInteger jika ingin mempertahankan ID dari JSON Shopee
            $table->bigIncrements('id'); // Mendefinisikan kolom 'id' sebagai primary key dengan tipe unsigned big integer.
            // Ini diasumsikan untuk menggunakan ID dari JSON Shopee.

            $table->string('name');                      // Kolom 'name' untuk nama internal kategori (misal: "Women Clothes")
            $table->string('display_name');              // Kolom 'display_name' untuk nama kategori yang ditampilkan ke pengguna (misal: "Pakaian Wanita")
            $table->string('slug')->unique(); // Kolom 'slug' untuk URL yang mudah dibaca dan unik, setelah 'display_name'

            $table->unsignedBigInteger('parent_id')->nullable(); // Kolom 'parent_id' sebagai foreign key yang menunjuk ke ID kategori lain dalam tabel yang sama (untuk hirarki), bisa NULL jika kategori adalah induk

            $table->boolean('has_active_children')->default(false); // Kolom boolean 'has_active_children' untuk menandai apakah kategori memiliki anak yang aktif, default FALSE
            $table->boolean('has_children')->default(false);       // Kolom boolean 'has_children' untuk menandai apakah kategori memiliki anak (aktif/tidak aktif), default FALSE

            // Kolom JSON untuk region_setting
            $table->json('region_setting')->nullable(); // Kolom 'region_setting' untuk menyimpan data JSON spesifik wilayah, bisa NULL

            $table->boolean('is_prohibit')->default(false); // Kolom boolean 'is_prohibit' untuk menandai kategori yang dilarang, default FALSE
            $table->integer('permit_status')->default(0);   // Kolom integer 'permit_status' untuk status izin kategori, default 0
            // Kolom `is_active` untuk mengontrol visibilitas kategori di frontend.
            $table->boolean('is_active')->default(true); // Kolom boolean 'is_active' dengan nilai default TRUE (aktif), setelah 'permit_status'

            $table->timestamps();                            // Menambahkan kolom 'created_at' dan 'updated_at' secara otomatis
            $table->softDeletes();                           // Menambahkan kolom 'deleted_at' untuk fitur soft delete

            // Definisi Foreign Key ke dirinya sendiri
            $table->foreign('parent_id')                     // Mendefinisikan 'parent_id' sebagai foreign key
                ->references('id')                           // Referensi ke kolom 'id' di tabel
                ->on('categories')                           // ... 'categories' (tabel saat ini)
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
