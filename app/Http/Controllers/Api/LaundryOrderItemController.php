<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryItem;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use Illuminate\Http\Request;

class LaundryOrderItemController extends Controller
{
    /**
     * List items by order
     */
    public function index(Request $request)
    {
        $request->validate([
            'laundry_order_id' => 'required|exists:laundry_orders,id',
        ]);

        $items = LaundryOrderItem::with('item')
            ->where('laundry_order_id', $request->laundry_order_id)
            ->get();

        return response()->json($items);
    }

    /**
     * Add / update item
     */
    public function store(Request $request)
    {
        $request->validate([
            'laundry_order_id' => 'required|exists:laundry_orders,id',
            'laundry_item_id'  => 'required|exists:laundry_items,id',
            'qty'              => 'required|integer|min:1',
        ]);

        $order = LaundryOrder::findOrFail($request->laundry_order_id);
        $item  = LaundryItem::findOrFail($request->laundry_item_id);

        $existing = LaundryOrderItem::where([
            'laundry_order_id' => $order->id,
            'laundry_item_id'  => $item->id,
        ])->first();

        if ($existing) {
            $existing->qty += $request->qty;
            $existing->subtotal = $existing->qty * $existing->price;
            $existing->save();
        } else {
            $existing = LaundryOrderItem::create([
                'laundry_order_id' => $order->id,
                'laundry_item_id'  => $item->id,
                'qty'              => $request->qty,
                'price'            => $item->price,
                'subtotal'         => $item->price * $request->qty,
            ]);
        }

        $this->recalculateTotal($order);

        return response()->json($existing, 201);
    }

    /**
     * Update qty
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'qty' => 'required|integer|min:0',
        ]);

        $item = LaundryOrderItem::findOrFail($id);

        if ($request->qty == 0) {
            $order = $item->order;
            $item->delete();
            $this->recalculateTotal($order);

            return response()->json(['message' => 'Item removed']);
        }

        $item->qty = $request->qty;
        $item->subtotal = $item->qty * $item->price;
        $item->save();

        $this->recalculateTotal($item->order);

        return response()->json($item);
    }

    /**
     * Remove item
     */
    public function destroy(string $id)
    {
        $item = LaundryOrderItem::findOrFail($id);
        $order = $item->order;

        $item->delete();
        $this->recalculateTotal($order);

        return response()->json(['message' => 'Item deleted']);
    }

    /**
     * Recalculate order total
     */
    private function recalculateTotal(LaundryOrder $order)
    {
        $itemsTotal = $order->items()->sum('subtotal');
        $addonsTotal = $order->addons()->sum('subtotal');

        $order->update([
            'total_price' => $itemsTotal + $addonsTotal
        ]);
    }
}
