<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrderItem extends Model
{
    use HasFactory;

    protected $table = 'laundry_order_items';

    protected $fillable = [
        'laundry_order_id', 'laundry_item_id', 'qty', 'price', 'subtotal'
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function item()
    {
        return $this->belongsTo(LaundryItem::class, 'laundry_item_id');
    }
}
