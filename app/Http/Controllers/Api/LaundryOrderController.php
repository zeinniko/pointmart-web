<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaundryOrderController extends Controller
{
    /**
     * List user laundry orders
     */
    public function index(Request $request)
    {
        $orders = LaundryOrder::with(['items', 'addons', 'package'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Create laundry order (HEADER ONLY)
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_id'   => 'nullable|exists:laundry_packages,id',
            'address_id'   => 'nullable|exists:user_addresses,id',
            'pickup_time'  => 'nullable|date',
            'delivery_time'=> 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $order = LaundryOrder::create([
            'order_code'    => 'LD-' . strtoupper(Str::random(8)),
            'user_id'       => $request->user()->id,
            'package_id'    => $request->package_id,
            'address_id'    => $request->address_id,
            'pickup_time'   => $request->pickup_time,
            'delivery_time' => $request->delivery_time,
            'payment_status'=> 'pending',
            'order_status'  => 'created',
            'total_price'   => 0,
            'notes'         => $request->notes,
        ]);

        return response()->json($order, 201);
    }

    /**
     * Show order detail
     */
    public function show(string $id)
    {
        $order = LaundryOrder::with(['items', 'addons', 'package'])
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Update order (status, address, time, payment)
     */
    public function update(Request $request, string $id)
    {
        $order = LaundryOrder::findOrFail($id);

        $order->update($request->only([
            'pickup_time',
            'delivery_time',
            'address_id',
            'payment_status',
            'order_status',
            'driver_id',
            'weight_input',
            'notes',
        ]));

        return response()->json($order);
    }

    /**
     * Delete order (only draft)
     */
    public function destroy(string $id)
    {
        $order = LaundryOrder::findOrFail($id);

        if ($order->order_status !== 'created') {
            return response()->json([
                'message' => 'Order cannot be deleted'
            ], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
