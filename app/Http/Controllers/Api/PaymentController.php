<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List payments / orders terpusat
     */
    public function index(Request $request)
    {
        $user =$request->user();

        $orders = Order::with([
            'user',
            'package',
            'address',
            'driver',
            'items.product',
            'items.laundryItem',
            'items.addon',
            'payment',
        ])
            ->where('order_status', '!=', 'cart')
            // Filter Berdasarkan Jenis Order (opsional: laundry, minimarket, marketplace)
            ->when($request->order_type, function ($query) use ($request) {
                $query->where('order_type',$request->order_type);
            })
            // Filter Status Order
            ->when($request->status, function ($query) use ($request) {
                $query->where('order_status',$request->status);
            })
            // Jika Role Driver / Deliver (role_id = 2)
            ->when($user &&$user->role_id == 2, function ($query) use ($user) {
                $query->where('driver_id',$user->id);
            })
            ->latest()
            ->get()
            // Group by Tanggal & User
            ->groupBy(function ($order) {
                return \Carbon\Carbon::parse($order->created_at)->format('Y-m-d');
            })
            ->map(function ($dateOrders) {
                return $dateOrders->groupBy('user_id');
            });

        return response()->json([
            'success' => true,
            'message' => 'List Order & Pembayaran',
            'data'    => $orders
        ]);
    }

    /**
     * Store payment (POS Minimarket, Laundry, & Marketplace)
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id'       => 'required|integer|exists:orders,id',
            'order_type'     => 'required|in:laundry,minimarket,marketplace',
            'payment_method' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $order = Order::findOrFail($request->order_id);
            $amount =$order->total_price;

            // 1. Update status order utama
            if ($request->order_type === 'minimarket') {$order->update([
                    'payment_status' => 'paid',
                    'order_status'   => 'completed',
                ]);
            } else {
                $order->update([
                    'payment_status' => 'paid',
                ]);
            }

            // 2. Simpan record pembayaran ke tabel `payments`
            $payment = Payment::updateOrCreate(
                [
                    'order_id'   => $order->id,
                    'order_type' => $request->order_type,
                ],
                [
                    'amount'           => $amount,
                    'method'           => $request->payment_method,
                    'status'           => 'paid',
                    'transaction_time' => now(),
                ]
            );

            return response()->json([
                'message' => 'Payment berhasil diproses',
                'data'    => $payment
            ], 201);
        });
    }

    /**
     * Show payment / order detail
     */
    public function show($id)
    {
        $order = Order::with([             'user',             'package',             'address',             'driver',             'items.product',             'items.laundryItem',             'items.addon',             'payment',         ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * Update status order & assign driver (khusus alur laundry / pengantaran)
     */
    public function update(Request $request, string$id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'order_status' => 'required|string',
            'driver_id'    => 'nullable|exists:users,id',
        ]);

        $newStatus =$request->order_status;

        $allowedTransitions = [
            'created' => ['process'],
            'process' => ['valid'],
            'valid'   => ['deliver'],
            'deliver' => ['finish'],
        ];

        $currentStatus =$order->order_status;

        if (
            isset($allowedTransitions[$currentStatus]) &&
            !in_array($newStatus, $allowedTransitions[$currentStatus])
        ) {
            return response()->json([
                'message' => 'Invalid status transition',
            ], 422);
        }

        // Saat status PROCESS, driver wajib dipilih
        if ($newStatus === 'process' && !$request->driver_id) {
            return response()->json([
                'message' => 'Driver wajib dipilih saat process order',
            ], 422);
        }

        if ($request->driver_id) {
            $order->driver_id =$request->driver_id;
        }

        // Otomatis tandai Lunas saat disetujui (STATUS VALID)
        if ($newStatus === 'valid') {$order->payment_status = 'paid';

            Payment::updateOrCreate(
                [
                    'order_id'   => $order->id,
                    'order_type' => $order->order_type ?? 'laundry',
                ],
                [
                    'amount'           => $order->total_price,
                    'method'           => 'cash',
                    'status'           => 'paid',
                    'transaction_time' => now(),
                ]
            );
        }

        $order->order_status =$newStatus;

        if ($newStatus === 'process' && !$order->pickup_time) {$order->pickup_time = now();
        }

        if ($newStatus === 'finish' && !$order->delivery_time) {$order->delivery_time = now();
        }

        $order->save();

        return response()->json([
            'message' => 'Order updated successfully',
            'data'    => $order->load(['user', 'driver', 'payment']),
        ]);
    }

    /**
     * Delete payment
     */
    public function destroy(string $id)
    {
        Payment::where('order_id', $id)->delete();

        return response()->json([
            'message' => 'Payment deleted'
        ]);
    }
}