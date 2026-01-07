<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of product categories (paginated, searchable, filterable)
     *
     * Query params:
     * - q: search by name
     * - is_active: 0|1
     * - include_products: 0|1
     * - per_page: integer
     */
    public function index(Request $request)
    {
        $query = ProductCategory::query();

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where('name', 'like', "%$keyword%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->boolean('include_products')) {
            $query->with('products');
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('name')->paginate($perPage);

        return ProductCategoryResource::collection($data);
    }

    /**
     * Store a newly created product category.
     */
    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = DB::transaction(fn () => ProductCategory::create($request->validated()));

        return (new ProductCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product category.
     */
    public function show(Request $request, ProductCategory $productCategory)
    {
        if ($request->boolean('include_products')) {
            $productCategory->load('products');
        }

        return new ProductCategoryResource($productCategory);
    }

    /**
     * Update the specified product category.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        DB::transaction(fn () => $productCategory->update($request->validated()));

        return (new ProductCategoryResource($productCategory->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified product category.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();
    
        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
    
}
