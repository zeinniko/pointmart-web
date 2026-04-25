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

    private function recalculateStock(int $productId): ProductStock
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

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
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

            return $this->recalculateStock($validated['product_id']);
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
            $request->only(['min_stock', 'updated_by'])
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
