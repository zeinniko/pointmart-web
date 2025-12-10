<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'laundry_status_history';

    protected $fillable = [
        'laundry_order_id', 'status', 'timestamp', 'driver_id'
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
