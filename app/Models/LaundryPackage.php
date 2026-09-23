<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryPackage extends Model
{
    use HasFactory;

    protected $table = 'laundry_packages';

    protected $fillable = [
        'name',
        'type',
        'category',
        'price_per_kg',
        'price_per_item',
        'min_kg',
        'estimation_time',
        'included_addons',
        'description',
        'is_active'
    ];

    protected $casts = [
        'included_addons' => 'array',
        'is_active' => 'boolean'
    ];

    public function items()
    {
        return $this->hasMany(LaundryItem::class, 'package_id');
    }
}
