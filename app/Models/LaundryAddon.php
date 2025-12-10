<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryAddon extends Model
{
    use HasFactory;

    protected $table = 'laundry_addons';

    protected $fillable = [
        'name', 'code', 'type', 'price', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function orderAddons()
    {
        return $this->hasMany(LaundryOrderAddon::class, 'addon_id');
    }
}
