<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\StockMovement;


class PosOrderController extends Controller
{
    /**
     * List POS orders
     */
    public function index()
    {
        return response()->json(
            PosOrder::with('items.product')
                ->get()
        );
    }

    /**
     * Store new POS order
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'payment_method'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $total = 0;
                $orderItemsData = [];

                Log::info('POS Order: Validating stock', ['items' => $request->items]);

                // === VALIDASI STOCK DULU ===
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $stock = ProductStock::where('product_id', $product->id)->lockForUpdate()->first();

                    if (!$stock || $stock->stock < $item['qty']) {
                        Log::warning('POS Order: Stock tidak cukup', [
                            'product_id' => $product->id,
                            'requested_qty' => $item['qty'],
                            'available_stock' => $stock->stock ?? 0
                        ]);
                        abort(422, "Stock tidak cukup untuk {$product->name}");
                    }

                    $subtotal = $product->price * $item['qty'];
                    $total += $subtotal;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'qty'        => $item['qty'],
                        'price'      => $product->price,
                        'subtotal'   => $subtotal,
                    ];
                }

                Log::info('POS Order: Stock valid, creating order', ['total' => $total]);

                // === CREATE ORDER ===
                $order = PosOrder::create([
                    'order_code'     => 'POS-' . strtoupper(Str::random(8)),
                    'cashier_id'     => auth()->id() ?? 1,
                    'total_price'    => $total,
                    'payment_method' => $request->payment_method ?? 'cash',
                ]);

                Log::info('POS Order: Order created', ['order_id' => $order->id]);

                // === INSERT ITEMS & REDUCE STOCK ===
                foreach ($orderItemsData as $itemData) {
                    PosOrderItem::create([
                        'pos_order_id' => $order->id,
                        ...$itemData,
                    ]);

                    // 🔥 STOCK MOVEMENT (OUT)
                    StockMovement::create([
                        'product_id' => $itemData['product_id'],
                        'type'       => 'out',
                        'qty'        => $itemData['qty'],
                        'notes'      => "POS Order {$order->order_code}",
                        'created_by' => auth()->id() ?? 1,
                        'created_at' => now()
                    ]);

                    // 🔄 RECALCULATE STOCK
                    $this->recalculateStock($itemData['product_id']);
                }


                Log::info('POS Order: All items inserted and stock updated', ['order_id' => $order->id]);

                // 🔥 LOAD SETELAH COMMIT
                $order->load('items.product');

                return response()->json([
                    'message' => 'Order berhasil dibuat',
                    'data'    => $order
                ], 201);
            } catch (\Exception $e) {
                Log::error('POS Order: Failed to create order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // agar transaksi rollback
            }
        });
    }

    /**
     * Show order detail
     */
    public function show(string $id)
    {
        return response()->json(
            PosOrder::with('items.product')->findOrFail($id)
        );
    }

    /**
     * Update order (LIMITED)
     * biasanya hanya payment_method / status (kalau ada)
     */
    public function update(Request $request, string $id)
    {
        $order = PosOrder::findOrFail($id);

        $order->update(
            $request->only(['payment_method'])
        );

        return response()->json([
            'message' => 'Order updated',
            'data'    => $order
        ]);
    }

    /**
     * Delete order (rollback stock)
     */
    public function destroy(string $id)
    {
        return DB::transaction(function () use ($id) {

            $order = PosOrder::with('items')->findOrFail($id);

            foreach ($order->items as $item) {

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type'       => 'in',
                    'qty'        => $item->qty,
                    'notes'      => "Cancel POS Order {$order->order_code}",
                    'created_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                ]);

                $this->recalculateStock($item->product_id);
            }


            $order->items()->delete();
            $order->delete();

            return response()->json([
                'message' => 'Order berhasil dihapus'
            ]);
        });
    }
}
