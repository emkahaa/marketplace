<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'order_number',
        'total_amount',
        'shipping_address_id',
        'shipping_cost',
        'shipping_method',
        'payment_method',
        'order_status',
        'payment_status',
        'notes',
        'uuid', // Tambahkan 'uuid' di fillable
    ];

    // --- Boot Method untuk UUID ---
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            // Generate order_number jika belum ada
            if (empty($model->order_number)) {
                $model->order_number = 'ORD-' . strtoupper(Str::random(8)) . '-' . time();
            }
        });
    }

    // --- Relasi ---
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Untuk Route Model Binding dengan UUID
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
