<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    /**
     * List stock movements (filterable)
     *
     * Query params:
     * - product_id
     * - type: in | out
     * - date_from (Y-m-d)
     * - date_to (Y-m-d)
     * - per_page
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'creator']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = (int) $request->get('per_page', 15);

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    /**
     * Store manual stock movement (ADMIN)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:in,out',
            'qty'        => 'required|integer|min:1',
            'notes'      => 'nullable|string',
        ]);

        $movement = StockMovement::create([
            ...$validated,
            'created_by' => auth()->id() ?? 1,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Stock movement recorded',
            'data'    => $movement->load('product', 'creator')
        ], 201);
    }

    /**
     * Show detail
     */
    public function show(string $id)
    {
        return response()->json(
            StockMovement::with(['product', 'creator'])->findOrFail($id)
        );
    }

    /**
     * Update notes only (audit safety)
     */
    public function update(Request $request, string $id)
    {
        $movement = StockMovement::findOrFail($id);

        $movement->update(
            $request->validate([
                'notes' => 'nullable|string'
            ])
        );

        return response()->json([
            'message' => 'Stock movement updated',
            'data'    => $movement
        ]);
    }

    /**
     * Delete (HARUS sangat dibatasi)
     */
    public function destroy(string $id)
    {
        StockMovement::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Stock movement deleted'
        ]);
    }
}
