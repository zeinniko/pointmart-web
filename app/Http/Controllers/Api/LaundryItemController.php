<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaundryItemRequest;
use App\Http\Requests\UpdateLaundryItemRequest;
use App\Http\Resources\LaundryItemResource;
use App\Models\LaundryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LaundryItemController extends Controller
{
    /**
     * Display a list of laundry items (paginated, searchable, filterable).
     *
     * Query params:
     * - q: search by item_name or category
     * - package_id: filter by package
     * - is_active: 0|1 filter active
     * - include_package: 0|1 load relation
     * - per_page: integer
     */
    public function index(Request $request)
    {
        $query = LaundryItem::query();

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('item_name', 'like', '%'.$keyword.'%')
                  ->orWhere('category', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->boolean('include_package')) {
            $query->with('package');
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('item_name')->paginate($perPage);

        return LaundryItemResource::collection($data);
    }

    /**
     * Store a newly created laundry item.
     */
    public function store(StoreLaundryItemRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $item = DB::transaction(function () use ($payload) {
            return LaundryItem::create($payload);
        });

        return (new LaundryItemResource($item->fresh('package')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified laundry item.
     */
    public function show(Request $request, LaundryItem $laundryItem)
    {
        if ($request->boolean('include_package')) {
            $laundryItem->load('package');
        }

        return new LaundryItemResource($laundryItem);
    }

    /**
     * Update the specified laundry item.
     */
    public function update(UpdateLaundryItemRequest $request, LaundryItem $laundryItem): JsonResponse
    {
        DB::transaction(function () use ($laundryItem, $request) {
            $laundryItem->update($request->validated());
        });

        return (new LaundryItemResource($laundryItem->fresh('package')))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified laundry item.
     */
    public function destroy(LaundryItem $laundryItem): JsonResponse
    {
        $laundryItem->delete();
        return response()->json(null, 204);
    }
}
