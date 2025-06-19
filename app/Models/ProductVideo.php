<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVideo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'video_url',
        'title',
        'description',
        'sort_order',
        'is_primary',
    ];

    // --- Relasi ---
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
