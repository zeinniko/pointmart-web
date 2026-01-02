<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PosOrder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * List payments
     */
    public function index()
    {
        return response()->json(
            Payment::latest()->paginate(10)
        );
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
    public function show(string $id)
    {
        return response()->json(
            Payment::findOrFail($id)
        );
    }

    /**
     * Update payment (rarely used)
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $payment->update(
            $request->only(['status', 'method'])
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
