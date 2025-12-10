<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRequest extends Model
{
    use HasFactory;

    protected $table = 'delivery_requests';

    protected $fillable = [
        'order_id',
        'order_type',
        'driver_id',
        'pickup_lat',
        'pickup_lng',
        'delivery_lat',
        'delivery_lng',
        'status',
        'assigned_at',
        'completed_at'
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(DeliveryStatusHistory::class, 'delivery_request_id');
    }
}
