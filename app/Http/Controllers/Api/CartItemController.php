<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartItemController extends Controller
{
    /**
     * GET /cart-items
     * List item cart user
     */
    public function index(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'message' => 'Cart kosong',
                'data' => []
            ]);
        }

        $items = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Item cart',
            'data' => $items
        ]);
    }

    /**
     * POST /cart-items
     * Tambah produk ke cart
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Ambil / buat cart
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        $product = Product::find($request->product_id);

        // Cek item sudah ada?
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->qty += $request->qty;
            $item->subtotal = $item->qty * $item->price;
            $item->save();
        } else {
            CartItem::create([
                'cart_id'   => $cart->id,
                'product_id'=> $product->id,
                'qty'       => $request->qty,
                'price'     => $product->price,
                'subtotal'  => $product->price * $request->qty
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke cart'
        ], 201);
    }

    /**
     * GET /cart-items/{id}
     */
    public function show(Request $request, string $id)
    {
        $item = CartItem::with('product')
            ->where('id', $id)
            ->whereHas('cart', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail item cart',
            'data' => $item
        ]);
    }

    /**
     * PUT /cart-items/{id}
     * Update qty
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        $item->qty = $request->qty;
        $item->subtotal = $item->qty * $item->price;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item cart diperbarui',
            'data' => $item
        ]);
    }

    /**
     * DELETE /cart-items/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $item = CartItem::where('id', $id)
            ->whereHas('cart', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item cart berhasil dihapus'
        ]);
    }
}
