<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Pastikan ini diimpor

class Category extends Model
{
    use HasFactory, SoftDeletes; // Gunakan trait SoftDeletes dan AuditableTrait

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'id',                 // Diperlukan karena ID diatur secara manual dari JSON di seeder
        'name',
        'display_name',
        'slug',
        'image',
        'parent_id',
        'is_active',          // Kolom baru untuk status aktif/tidak aktif
        'has_active_children',
        'has_children',
        'region_setting',
        'is_prohibit',
        'permit_status',
        'status',
        'meta_title',
        'meta_description',
    ];

    // Casting tipe data untuk kolom-kolom tertentu
    protected $casts = [
        'region_setting' => 'array',          // Mengubah kolom JSON ke array PHP
        'is_active' => 'boolean',             // Mengubah 0/1 dari DB ke boolean PHP
        'has_active_children' => 'boolean',   // Mengubah 0/1 dari DB ke boolean PHP
        'has_children' => 'boolean',          // Mengubah 0/1 dari DB ke boolean PHP
        'is_prohibit' => 'boolean',           // Mengubah 0/1 dari DB ke boolean PHP
        'permit_status' => 'integer',         // Memastikan ini adalah integer
        'deleted_at' => 'datetime',           // Casting untuk soft deletes
    ];

    // --- Relasi ---
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Scope untuk mengambil hanya kategori root (tanpa parent)
    public function scopeRoot(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    // Methods untuk mengupdate has_children dan has_active_children secara otomatis
    // Ini akan dipanggil setelah operasi CRUD (create, update, delete, restore)
    public static function boot()
    {
        parent::boot();

        // Saat kategori dibuat atau diperbarui, perbarui flag pada kategori induk
        static::saved(function ($category) {
            if ($category->parent) {
                $category->parent->updateChildrenFlags();
            }
        });

        // Saat kategori dihapus (soft deleted), perbarui flag pada kategori induk
        static::deleted(function ($category) {
            if ($category->parent) {
                $category->parent->updateChildrenFlags();
            }
        });

        // Saat kategori dipulihkan dari soft delete, perbarui flag pada kategori induk
        static::restored(function ($category) {
            if ($category->parent) {
                $category->parent->updateChildrenFlags();
            }
        });
    }

    /**
     * Memperbarui flag has_children dan has_active_children untuk kategori ini.
     */
    public function updateChildrenFlags()
    {
        $this->has_children = $this->children()->exists();
        // has_active_children dihitung berdasarkan anak-anak yang 'is_active'
        $this->has_active_children = $this->children()->where('is_active', true)->exists();
        $this->save();
    }
}
