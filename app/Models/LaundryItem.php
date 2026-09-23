<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryItem extends Model
{
    use HasFactory;

    protected $table = 'laundry_items';

    protected $fillable = [
        'package_id', 'item_name', 'price', 'category', 'image', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function package()
    {
        return $this->belongsTo(LaundryPackage::class, 'package_id');
    }
}
