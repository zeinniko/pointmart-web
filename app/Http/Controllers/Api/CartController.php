<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * GET /carts
     * Ambil cart user login (1 user = 1 cart)
     */
    public function index(Request $request)
    {
        $cart = Cart::with('items.product')
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Cart user',
            'data' => $cart
        ]);
    }

    /**
     * POST /carts
     * Buat cart manual (optional)
     */  
    public function store(Request $request)
    {
        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart berhasil dibuat',
            'data' => $cart
        ], 201);
    }

    /**
     * GET /carts/{id}
     */
    public function show(Request $request, string $id)
    {
        $cart = Cart::with('items.product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail cart',
            'data' => $cart
        ]);
    }

    /**
     * PUT /carts/{id}
     * (Biasanya tidak dipakai)
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Cart tidak bisa diubah secara langsung'
        ], 400);
    }

    /**
     * DELETE /carts/{id}
     * Kosongkan cart
     */
    public function destroy(Request $request, string $id)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart tidak ditemukan'
            ], 404);
        }

        $cart->items()->delete();
        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart berhasil dikosongkan'
        ]);
    }
}
