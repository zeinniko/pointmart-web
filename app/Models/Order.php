<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'order_type',     // 'minimarket' | 'laundry' | 'marketplace'
        'user_id',
        'package_id',    // Khusus Laundry
        'weight_input',  // Khusus Laundry
        'address_id',
        'pickup_time',   // Khusus Laundry
        'delivery_time', // Khusus Laundry
        'total_price',
        'payment_status',
        'order_status',
        'driver_id',
        'notes'
    ];

    protected $casts = [
        'pickup_time'   => 'datetime',
        'delivery_time' => 'datetime',
        'weight_input'  => 'float',
        'total_price'   => 'float',
    ];

    /* ================= RELASI ================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function package()
    {
        return $this->belongsTo(LaundryPackage::class, 'package_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }
}