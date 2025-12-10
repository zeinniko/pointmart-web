<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products (searchable, filterable, paginated)
     *
     * Query params:
     * - q: search name or barcode
     * - category_id: filter by category
     * - is_active: 0|1
     * - include_category: 0|1
     * - include_stock: 0|1
     * - per_page: integer (default 15)
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                  ->orWhere('barcode', 'like', "%$keyword%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->boolean('include_category')) {
            $query->with('category');
        }

        if ($request->boolean('include_stock')) {
            $query->with('stock');
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('name')->paginate($perPage);

        return ProductResource::collection($data);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(fn () => Product::create($request->validated()));

        // biasanya UI langsung butuh stock, tapi tidak dipaksakan
        return (new ProductResource($product->load('category', 'stock')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, Product $product)
    {
        if ($request->boolean('include_category')) {
            $product->load('category');
        }

        if ($request->boolean('include_stock')) {
            $product->load('stock');
        }

        return new ProductResource($product);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        DB::transaction(fn () => $product->update($request->validated()));

        return (new ProductResource($product->fresh()->load('category', 'stock')))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }
}
