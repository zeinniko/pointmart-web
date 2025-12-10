<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrder extends Model
{
    use HasFactory;

    protected $table = 'laundry_orders';

    protected $fillable = [
        'order_code',
        'user_id',
        'package_id',
        'address_id',
        'pickup_time',
        'delivery_time',
        'weight_input',
        'total_price',
        'payment_status',
        'order_status',
        'driver_id',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(LaundryPackage::class, 'package_id');
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items()
    {
        return $this->hasMany(LaundryOrderItem::class, 'laundry_order_id');
    }

    public function addons()
    {
        return $this->hasMany(LaundryOrderAddon::class, 'laundry_order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(LaundryStatusHistory::class, 'laundry_order_id');
    }
}
