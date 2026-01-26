<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaundryAddon;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderAddon;
use Illuminate\Http\Request;

class LaundryOrderAddonController extends Controller
{
    /**
     * List addons by order
     */
    public function index(Request $request)
    {
        $request->validate([
            'laundry_order_id' => 'required|exists:laundry_orders,id',
        ]);

        $addons = LaundryOrderAddon::with('addon')
            ->where('laundry_order_id', $request->laundry_order_id)
            ->get();

        return response()->json($addons);
    }

    /**
     * Add / update addon
     */
    public function store(Request $request)
    {
        $request->validate([
            'laundry_order_id' => 'required|exists:laundry_orders,id',
            'addon_id'         => 'required|exists:laundry_addons,id',
            'quantity'         => 'required|integer|min:1',
        ]);

        $order = LaundryOrder::findOrFail($request->laundry_order_id);
        $addon = LaundryAddon::findOrFail($request->addon_id);

        $existing = LaundryOrderAddon::where([
            'laundry_order_id' => $order->id,
            'addon_id'         => $addon->id,
        ])->first();

        if ($existing) {
            $existing->quantity += $request->quantity;
            $existing->subtotal = $existing->quantity * $existing->price;
            $existing->save();
        } else {
            $existing = LaundryOrderAddon::create([
                'laundry_order_id' => $order->id,
                'addon_id'         => $addon->id,
                'price'            => $addon->price,
                'quantity'         => $request->quantity,
                'subtotal'         => $addon->price * $request->quantity,
            ]);
        }

        $this->recalculateTotal($order);

        return response()->json($existing, 201);
    }

    /**
     * Update addon quantity
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $addon = LaundryOrderAddon::findOrFail($id);
        $order = $addon->order;

        if ($request->quantity == 0) {
            $addon->delete();
            $this->recalculateTotal($order);

            return response()->json(['message' => 'Addon removed']);
        }

        $addon->quantity = $request->quantity;
        $addon->subtotal = $addon->quantity * $addon->price;
        $addon->save();

        $this->recalculateTotal($order);

        return response()->json($addon);
    }

    /**
     * Remove addon
     */
    public function destroy(string $id)
    {
        $addon = LaundryOrderAddon::findOrFail($id);
        $order = $addon->order;

        $addon->delete();
        $this->recalculateTotal($order);

        return response()->json(['message' => 'Addon deleted']);
    }

    /**
     * Recalculate order total
     */
    private function recalculateTotal(LaundryOrder $order)
    {
        $itemsTotal  = $order->items()->sum('subtotal');
        $addonsTotal = $order->addons()->sum('subtotal');

        $order->update([
            'total_price' => $itemsTotal + $addonsTotal
        ]);
    }
}
