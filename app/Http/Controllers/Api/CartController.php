<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


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
        Log::info('[CART][STORE] Request masuk', [
            'user_id' => optional($request->user())->id,
        ]);
    
        $cart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['created_at' => now()]
        );
    
        Log::info('[CART][STORE] Cart diproses', [
            'user_id' => $request->user()->id,
            'cart_id' => $cart->id,
            'status'  => $cart->wasRecentlyCreated ? 'CREATED' : 'EXISTING',
        ]);
    
        return response()->json([
            'success' => true,
            'message' => $cart->wasRecentlyCreated
                ? 'Cart berhasil dibuat'
                : 'Cart sudah tersedia',
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
        Log::warning('[CART][UPDATE] Update ditolak', [
            'cart_id' => $id,
            'user_id' => optional($request->user())->id,
            'payload' => $request->all(),
        ]);
    
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
