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
        $user = $request->user();

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
            ->where('order_status', '!=', LaundryOrder::STATUS_CART)

            // FILTER STATUS
            ->when($request->status, function ($query) use ($request) {
                $query->where('order_status', $request->status);
            })

            // JIKA ROLE DRIVER / DELIVER
            ->when($user->role_id == 2, function ($query) use ($user) {
                $query->where('driver_id', $user->id);
            })

            ->latest()
            ->get()

            // GROUP BY TANGGAL
            ->groupBy(function ($order) {
                return \Carbon\Carbon::parse($order->created_at)
                    ->format('Y-m-d');
            })

            // GROUP BY USER
            ->map(function ($dateOrders) {
                return $dateOrders->groupBy('user_id');
            });

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

        // ambil tanggal order laundry
        $orderDate = \Carbon\Carbon::parse($order->created_at)->format('Y-m-d');

        // ambil marketplace order dengan tanggal yang sama
        $marketplaceOrders = Order::with([
            'items.product',
            'address'
        ])
            ->where('user_id', $order->user_id)
            ->whereDate('created_at', $orderDate) // 🔥 filter tanggal
            ->where('order_status', LaundryOrder::STATUS_CREATED)
            ->latest()
            ->get();

        return response()->json([
            'data' => $order,
            'marketplace_orders' => $marketplaceOrders->isEmpty()
                ? null
                : $marketplaceOrders
        ]);
    }

    /**
     * Update status order laundry
     */
    public function update(Request $request, string $id)
    {
        $order = LaundryOrder::findOrFail($id);

        $request->validate([
            'order_status' => 'required|string',
            'driver_id'    => 'nullable|exists:users,id',
        ]);

        $newStatus = $request->order_status;

        /**
         * Validasi flow status
         *
         * cart -> created
         * created -> process
         * process -> valid
         * valid -> deliver
         * deliver -> finish
         */

        $allowedTransitions = [
            LaundryOrder::STATUS_CREATED => [
                LaundryOrder::STATUS_PROCESS,
            ],

            LaundryOrder::STATUS_PROCESS => [
                LaundryOrder::STATUS_VALID,
            ],

            LaundryOrder::STATUS_VALID => [
                LaundryOrder::STATUS_DELIVER,
            ],

            LaundryOrder::STATUS_DELIVER => [
                LaundryOrder::STATUS_FINISH,
            ],
        ];

        $currentStatus = $order->order_status;

        if (
            isset($allowedTransitions[$currentStatus]) &&
            !in_array($newStatus, $allowedTransitions[$currentStatus])
        ) {
            return response()->json([
                'message' => 'Invalid status transition',
            ], 422);
        }

        /**
         * Saat PROCESS wajib pilih driver
         */
        if (
            $newStatus === LaundryOrder::STATUS_PROCESS &&
            !$request->driver_id
        ) {
            return response()->json([
                'message' => 'Driver wajib dipilih saat process order',
            ], 422);
        }

        /**
         * Assign driver
         */
        if ($request->driver_id) {
            $order->driver_id = $request->driver_id;
        }

        /**
         * Auto payment paid saat valid
         */
        /**
         * Auto payment paid saat valid
         */
        if ($newStatus === LaundryOrder::STATUS_VALID) {

            $order->payment_status = 'paid';

            Payment::updateOrCreate(
                [
                    'order_id'   => $order->id,
                    'order_type' => 'laundry',
                ],
                [
                    'amount'           => $order->total_price,
                    'method'           => 'cash',
                    'status'           => 'paid',
                    'transaction_time' => now(),
                ]
            );
        }

        /**
         * Update status
         */
        $order->order_status = $newStatus;

        /**
         * Optional pickup & delivery time
         */
        if (
            $newStatus === LaundryOrder::STATUS_PROCESS &&
            !$order->pickup_time
        ) {
            $order->pickup_time = now();
        }

        if (
            $newStatus === LaundryOrder::STATUS_FINISH &&
            !$order->delivery_time
        ) {
            $order->delivery_time = now();
        }

        $order->save();

        return response()->json([
            'message' => 'Order updated successfully',
            'data'    => $order->load([
                'user',
                'driver',
                'payment',
                'invoice',
            ]),
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
