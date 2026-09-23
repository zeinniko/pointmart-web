<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PosOrderController extends Controller
{
    /**
     * List POS Orders dari tabel orders
     */
    public function index()
    {
        return response()->json(
            Order::with('items.product')
                ->where('order_code', 'like', 'POS-%')
                ->latest()
                ->get()
        );
    }

    /**
     * Store new POS order ke tabel `orders` dan `order_items`
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'payment_method'     => 'nullable|string',
            'customer_name'      => 'nullable|string',
            'customer_phone'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $total = 0;
                $orderItemsData = [];

                Log::info('POS Order: Validating stock', ['items' => $request->items]);

                // === 1. VALIDASI STOK ===
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $stock = ProductStock::where('product_id', $product->id)->lockForUpdate()->first();

                    if (!$stock || $stock->stock < $item['qty']) {
                        Log::warning('POS Order: Stock tidak cukup', [
                            'product_id'      => $product->id,
                            'requested_qty'   => $item['qty'],
                            'available_stock' => $stock->stock ?? 0,
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

                // Catat Info Pelanggan di Notes jika ada
                $notes = null;
                if ($request->hasAny(['customer_name', 'customer_phone'])) {
                    $notes = "Pelanggan: {$request->customer_name} ({$request->customer_phone})";
                }

                // === 2. CREATE ORDER DI TABEL `orders` ===
                $order = Order::create([
                    'order_code'     => 'POS-' . strtoupper(Str::random(8)),
                    'user_id'        => auth()->id() ?? $request->user_id ?? null,
                    'total_price'    => $total,
                    'payment_status' => 'paid',      // Match Enum: 'pending','paid','cash'
                    'order_status'   => 'completed', // Status langsung selesai
                    'notes'          => $notes,
                ]);

                Log::info('POS Order: Order created on orders table', ['order_id' => $order->id]);

                // === 3. INSERT ORDER ITEMS & POTONG STOK ===
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $itemData['product_id'],
                        'qty'        => $itemData['qty'],
                        'price'      => $itemData['price'],
                        'subtotal'   => $itemData['subtotal'],
                    ]);

                    // Catat riwayat stok keluar
                    StockMovement::create([
                        'product_id' => $itemData['product_id'],
                        'type'       => 'out',
                        'qty'        => $itemData['qty'],
                        'notes'      => "POS Order {$order->order_code}",
                        'created_by' => auth()->id() ?? 1,
                        'created_at' => now(),
                    ]);

                    // Hitung ulang stok
                    $this->recalculateStock($itemData['product_id']);
                }

                $order->load('items.product');

                return response()->json([
                    'message' => 'Order POS berhasil disimpan',
                    'data'    => $order,
                ], 201);
            } catch (\Exception $e) {
                Log::error('POS Order: Failed to create order', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Show order detail
     */
    public function show(string $id)
    {
        return response()->json(
            Order::with('items.product')->findOrFail($id)
        );
    }

    /**
     * Update order
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $order->update(
            $request->only(['payment_status', 'order_status', 'notes'])
        );

        return response()->json([
            'message' => 'Order updated',
            'data'    => $order,
        ]);
    }

    /**
     * Delete order (Rollback Stock)
     */
    public function destroy(string $id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::with('items')->findOrFail($id);

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
                'message' => 'Order berhasil dihapus',
            ]);
        });
    }

    /**
     * Recalculate Stock Helper
     */
    private function recalculateStock(int $productId): void
    {
        $inQty = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->sum('qty');

        $outQty = StockMovement::where('product_id', $productId)
            ->where('type', 'out')
            ->sum('qty');

        $currentStock = max(0, $inQty - $outQty);

        ProductStock::updateOrCreate(
            ['product_id' => $productId],
            ['stock' => $currentStock]
        );
    }
}
