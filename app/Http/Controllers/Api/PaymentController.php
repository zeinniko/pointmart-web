<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\LaundryOrder;
use App\Models\PosOrder;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * List payments
     */

    public function index(Request $request)
    {
        $orders = LaundryOrder::with([
            'user',
            'package',
            'address',
            'driver',
            'items',
            'addons',
            'payment',
            'invoice'
        ])
            ->where('order_status', '!=', 'cart')
            ->when($request->status, function ($query) use ($request) {
                $query->where('order_status', $request->status);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'List Order',
            'data' => $orders
        ]);
    }

    /**
     * Store payment (POS)
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:pos_orders,id',
            'payment_method'     => 'required|string',
            'order_type'     => 'required|string',
        ]);

        $order = PosOrder::findOrFail($request->order_id);

        $payment = Payment::create([
            'order_id'         => $order->id,
            'order_type'       => $request->order_type,
            'amount'           => $order->total_price,
            'method'           => $request->payment_method,
            'status'           => 'paid',
            'transaction_time' => now(),
        ]);

        return response()->json([
            'message' => 'Payment berhasil',
            'data'    => $payment
        ], 201);
    }

    /**
     * Show payment detail
     */
    public function show($id)
    {
        $order = LaundryOrder::with([
            'user',
            'package',
            'address',
            'items.item',
            'addons.addon',
            'payment',
            'invoice'
        ])->findOrFail($id);

        // ambil order marketplace berdasarkan user
        $marketplaceOrders = Order::with([
            'items.product',
            'address'
        ])
            ->where('user_id', $order->user_id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $order,
            'marketplace_orders' => $marketplaceOrders
        ]);
    }

    /**
     * Update payment (rarely used)
     */
    public function update(Request $request, string $id)
    {
        $payment = LaundryOrder::findOrFail($id);

        $payment->update(
            $request->only(['order_status'])
        );

        return response()->json([
            'message' => 'Payment updated',
            'data'    => $payment
        ]);
    }

    /**
     * Delete payment
     */
    public function destroy(string $id)
    {
        Payment::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Payment deleted'
        ]);
    }
}
