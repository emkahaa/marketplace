<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'label',
        'recipient_name',
        'phone_number',
        'province',
        'city',
        'district',
        'village',
        'detail_address',
        'postal_code',
        'latitude',
        'longitude',
        'is_default',
    ];

    // --- Relasi Polymorphic ---
    public function addressable()
    {
        return $this->morphTo();
    }

    // --- Helper Scope ---
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
