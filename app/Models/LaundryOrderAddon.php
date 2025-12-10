<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrderAddon extends Model
{
    use HasFactory;

    protected $table = 'laundry_order_addons';

    protected $fillable = [
        'laundry_order_id', 'addon_id', 'price', 'quantity', 'subtotal'
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function addon()
    {
        return $this->belongsTo(LaundryAddon::class, 'addon_id');
    }
}
