<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaundryAddonRequest;
use App\Http\Requests\UpdateLaundryAddonRequest;
use App\Http\Resources\LaundryAddonResource;
use App\Models\LaundryAddon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LaundryAddonController extends Controller
{
    /**
     * Display a listing of laundry addons (paginated, searchable, filterable)
     *
     * Query params:
     * - q: search by name or code
     * - type: filter by type
     * - is_active: 0|1
     * - per_page: integer
     */
    public function index(Request $request)
    {
        $query = LaundryAddon::query();

        $keyword = $request->get('q') ?? $request->get('search');
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
    
        if ($request->has('is_active') && $request->is_active !== 'all' && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('name')->paginate($perPage);

        return LaundryAddonResource::collection($data);
    }

    /**
     * Store a newly created laundry addon.
     */
    public function store(StoreLaundryAddonRequest $request): JsonResponse
    {
        $addon = DB::transaction(fn () => LaundryAddon::create($request->validated()));

        return (new LaundryAddonResource($addon))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified laundry addon.
     */
    public function show(LaundryAddon $laundryAddon)
    {
        return new LaundryAddonResource($laundryAddon);
    }

    /**
     * Update the specified laundry addon.
     */
    public function update(UpdateLaundryAddonRequest $request, LaundryAddon $laundryAddon): JsonResponse
    {
        DB::transaction(fn () => $laundryAddon->update($request->validated()));

        return (new LaundryAddonResource($laundryAddon->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified laundry addon.
     */
    public function destroy(LaundryAddon $laundryAddon): JsonResponse
    {
        $laundryAddon->delete();
        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
