<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\LaundryPackage;
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
            'delivery_time' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $order = LaundryOrder::create([
            'order_code'    => 'LD-' . strtoupper(Str::random(8)),
            'user_id'       => $request->user()->id,
            'package_id'    => $request->package_id,
            'address_id'    => $request->address_id,
            'pickup_time'   => $request->pickup_time,
            'delivery_time' => $request->delivery_time,
            'payment_status' => 'pending',
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

    public function addToCart(Request $request)
    {
        $request->validate([
            'package_id'   => 'required|exists:laundry_packages,id',
            'weight_input' => 'nullable|numeric|min:1',
        ]);

        $user = $request->user();

        $package = LaundryPackage::findOrFail($request->package_id);

        if ($package->min_kg && $request->weight_input < $package->min_kg) {
            return response()->json([
                'message' => 'Minimal ' . $package->min_kg . ' Kg'
            ], 400);
        }

        $total = $package->price_per_kg * $request->weight_input;

        $order = LaundryOrder::create([
            'order_code'    => 'LD-' . strtoupper(Str::random(8)),
            'user_id'       => $user->id,
            'package_id'    => $package->id,
            'weight_input'  => $request->weight_input,
            'total_price'   => $total,
            'payment_status' => 'pending',
            'order_status'  => 'cart',
        ]);

        return response()->json([
            'message' => 'Added to cart',
            'data' => $order->load('package')
        ], 201);
    }

    public function getCart(Request $request)
    {
        $orders = LaundryOrder::with('package')
            ->where('user_id', $request->user()->id)
            ->where('order_status', 'cart')
            ->latest()
            ->get();
    
        $grandTotal = $orders->sum('total_price');
    
        return response()->json([
            'message' => 'Cart fetched',
            'total_item' => $orders->count(),
            'grand_total' => $grandTotal,
            'data' => $orders
        ]);
    }

    public function removeCart($id, Request $request)
    {
        $user = $request->user();

        $order = LaundryOrder::where('id', $id)
            ->where('user_id', $user->id) // 🔥 biar gak bisa hapus punya orang lain
            ->where('order_status', 'cart') // 🔥 hanya cart yang boleh dihapus
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        $order->delete();

        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }
}
