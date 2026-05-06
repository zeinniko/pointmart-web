<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\LaundryPackage;
use App\Models\LaundryItem;
use App\Models\LaundryOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
            'package_id'   => 'nullable|exists:laundry_packages,id',
            'weight_input' => 'nullable|numeric|min:1',
            'order_items'  => 'sometimes|array',
            'order_items.*.laundry_item_id' => 'required_with:order_items|exists:laundry_items,id',
            'order_items.*.qty' => 'required_with:order_items|integer|min:1',
        ]);

        $user = $request->user();

        $total = 0;

        // =========================================
        // CASE 1: LAUNDRY SATUAN (ADA order_items)
        // =========================================
        if ($request->has('order_items') && count($request->order_items) > 0) {

            $order = LaundryOrder::create([
                'order_code'    => 'LD-' . strtoupper(Str::random(8)),
                'user_id'       => $user->id,
                'package_id'    => null,
                'weight_input'  => null,
                'total_price'   => 0,
                'payment_status' => 'pending',
                'order_status'  => 'cart',
            ]);

            foreach ($request->order_items as $itemData) {
                $laundryItem = LaundryItem::findOrFail($itemData['laundry_item_id']);
                $subtotal = $laundryItem->price * $itemData['qty'];

                LaundryOrderItem::create([
                    'laundry_order_id' => $order->id,
                    'laundry_item_id'  => $laundryItem->id,
                    'qty'              => $itemData['qty'],
                    'price'            => $laundryItem->price,
                    'subtotal'         => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_price' => $total]);

            return response()->json([
                'message' => 'Added to cart (Satuan)',
                'data' => $order->load(['items.item'])
            ], 201);
        }

        // =========================================
        // CASE 2: LAUNDRY KILOAN (PACKAGE)
        // =========================================
        if (!$request->package_id) {
            return response()->json([
                'message' => 'package_id wajib jika tanpa order_items'
            ], 400);
        }

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
            'message' => 'Added to cart (Kiloan)',
            'data' => $order->load(['package'])
        ], 201);
    }

    public function getCart(Request $request)
    {
        $orders = LaundryOrder::with(['package', 'items.item', 'addons'])
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
            ->where('user_id', $user->id)
            ->where('order_status', 'cart')
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

    public function checkout(Request $request)
    {
        $user = $request->user();
        // ambil default address
        $defaultAddress = $user->addresses()
            ->where('is_default', true)
            ->first();

        if (!$defaultAddress) {
            return response()->json([
                'message' => 'Alamat default belum dipilih'
            ], 400);
        }

        // ================= MARKET =================
        if ($request->is_included_market_item) {
            $items = $request->items;

            if (empty($items)) {
                return response()->json([
                    'message' => 'Item kosong'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $order = Order::create([
                    'order_code' => 'ORD-' . time(),
                    'payment_status' => 'pending',
                    'user_id'    => $user->id,
                    'address_id' => $defaultAddress->id,
                    'order_status' => 'created',
                    'total_price' => 0,
                ]);
                $total = 0;

                foreach ($items as $item) {
                    $product = Product::find($item['product_id']);

                    if (!$product) {
                        throw new \Exception('Product tidak ditemukan');
                    }

                    $subtotal = $product->price * $item['qty'];

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'price'      => $product->price,
                        'qty'        => $item['qty'],
                        'subtotal'   => $subtotal,
                    ]);

                    Log::info('ORDER ITEM CREATED', [
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'subtotal' => $subtotal
                    ]);

                    $total += $subtotal;
                }

                $order->update([
                    'total_price' => $total
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Order market berhasil dibuat',
                    'order_id' => $order->id
                ]);
            } catch (\Exception $e) {

                DB::rollBack();

                Log::error('MARKET CHECKOUT ERROR', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        // ================= LAUNDRY =================
        $orders = LaundryOrder::where('user_id', $user->id)
            ->where('order_status', 'cart')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'Cart kosong'
            ], 400);
        }

        DB::beginTransaction();

        try {

            foreach ($orders as $laundry_order) {

                $laundry_order->order_status = 'created';
                $laundry_order->address_id = $defaultAddress->id;
                $laundry_order->save();
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order laundry berhasil dibuat',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
