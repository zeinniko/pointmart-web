<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',       // Untuk POS Minimarket & Produk Marketplace
        'laundry_item_id',  // Untuk Item Baju/Pakaian Laundry
        'addon_id',         // Untuk Layanan Tambahan Laundry
        'qty',
        'price',
        'subtotal'
    ];

    protected $casts = [
        'qty'      => 'integer',
        'price'    => 'float',
        'subtotal' => 'float',
    ];

    /* ================= RELASI ================= */

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function laundryItem()
    {
        return $this->belongsTo(LaundryItem::class, 'laundry_item_id');
    }

    public function addon()
    {
        return $this->belongsTo(LaundryAddon::class, 'addon_id');
    }
}