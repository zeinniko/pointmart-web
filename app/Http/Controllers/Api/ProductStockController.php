<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductStockRequest;
use App\Http\Requests\UpdateProductStockRequest;
use App\Http\Resources\ProductStockResource;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\StockMovement;

class ProductStockController extends Controller
{

    private function recalculateStock(int $productId, int $minStock): ProductStock
    {
        $stockIn = StockMovement::where('product_id', $productId)
            ->where('type', 'in')
            ->sum('qty');

        $stockOut = StockMovement::where('product_id', $productId)
            ->where('type', 'out')
            ->sum('qty');

        $finalStock = $stockIn - $stockOut;

        return ProductStock::updateOrCreate(
            ['product_id' => $productId],
            [
                'stock'      => $finalStock,
                'min_stock'      => $minStock,
                'updated_by' => auth()->id() ?? 1,
            ]
        );
    }

    /**
     * Display a listing of product stock with optional eager loading.
     *
     * Query params:
     * - product_id: filter by product
     * - include_product: 0|1
     * - include_updated_by: 0|1
     * - per_page: integer (default 15)
     */
    public function index(Request $request)
    {
        $query = ProductStock::query();

        $keyword = $request->get('q') ?? $request->get('search');
        if (!empty($keyword)) {
            $query->whereHas('product', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('barcode', 'like', "%{$keyword}%");
            });
        }

        $productId = $request->get('product_id') ?? $request->get('product');
        if (!empty($productId) && $productId !== 'all') {
            $query->where('product_id', $productId);
        }

        $categoryId = $request->get('category_id') ?? $request->get('category');
        if (!empty($categoryId) && $categoryId !== 'all') {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('product_category_id', $categoryId);
            });
        }

        $status = $request->get('status') ?? $request->get('stock_status');
        if (!empty($status) && $status !== 'all') {
            if ($status === 'low') {
                $query->whereNotNull('min_stock')
                      ->where('min_stock', '>', 0)
                      ->whereColumn('stock', '<=', 'min_stock');
            } elseif ($status === 'normal') {
                $query->where(function ($q) {
                    $q->whereNull('min_stock')
                      ->orWhere('min_stock', 0)
                      ->orWhereColumn('stock', '>', 'min_stock');
                });
            }
        }

        $query->with('product');

        if ($request->boolean('include_updated_by')) {
            $query->with('updatedBy');
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('id', 'desc')->paginate($perPage);

        return ProductStockResource::collection($data);
    }

    /**
     * Create / update product stock (if exists → update, else → create)
     */
    public function store(StoreProductStockRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $stock = DB::transaction(function () use ($validated) {

            StockMovement::create([
                'product_id' => $validated['product_id'],
                'type'       => 'in', // in | out
                'qty'        => $validated['stock'],
                'notes'      => 'add stock by admin' ?? null,
                'created_by' => auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            return $this->recalculateStock($validated['product_id'], $validated['min_stock']);
        });

        return (new ProductStockResource(
            $stock->load('product', 'updatedBy')
        ))->response()->setStatusCode(201);
    }

    /**
     * Show a single product stock.
     */
    public function show(Request $request, ProductStock $productStock)
    {
        if ($request->boolean('include_product')) {
            $productStock->load('product');
        }

        if ($request->boolean('include_updated_by')) {
            $productStock->load('updatedBy');
        }

        return new ProductStockResource($productStock);
    }

    /**
     * Update product stock.
     */
    public function update(UpdateProductStockRequest $request, ProductStock $productStock): JsonResponse
    {
        $productStock->update(
            $request->only(['min_stock', 'stock', 'updated_by'])
        );

        return (new ProductStockResource(
            $productStock->fresh()->load('product', 'updatedBy')
        ))->response()->setStatusCode(200);
    }


    /**
     * Delete stock entry — jarang dipakai karena stock penting, tapi tetap disiapkan
     */
    public function destroy(ProductStock $productStock): JsonResponse
    {
        $productStock->delete();
        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
