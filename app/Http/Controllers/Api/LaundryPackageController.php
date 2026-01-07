<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaundryPackageRequest;
use App\Http\Requests\UpdateLaundryPackageRequest;
use App\Http\Resources\LaundryPackageResource;
use App\Models\LaundryPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LaundryPackageController extends Controller
{
    /**
     * Display a paginated listing of laundry packages.
     *
     * Query params:
     * - q: search by name or category (partial)
     * - is_active: 0|1 filter active
     * - include_items: 0|1 include related items
     * - per_page: integer
     */
    public function index(Request $request)
    {
        $query = LaundryPackage::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($qbuilder) use ($q) {
                $qbuilder->where('name', 'like', '%'.$q.'%')
                         ->orWhere('category', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->boolean('include_items')) {
            $query->with('items');
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $query->orderBy('name')->paginate($perPage);

        return LaundryPackageResource::collection($data);
    }

    /**
     * Store a newly created laundry package.
     */
    public function store(StoreLaundryPackageRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $package = DB::transaction(function () use ($payload) {
            return LaundryPackage::create($payload);
        });

        return (new LaundryPackageResource($package->fresh('items')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified package.
     */
    public function show(Request $request, LaundryPackage $laundryPackage)
    {
        if ($request->boolean('include_items')) {
            $laundryPackage->load('items');
        }
        return new LaundryPackageResource($laundryPackage);
    }

    /**
     * Update the specified package.
     */
    public function update(UpdateLaundryPackageRequest $request, LaundryPackage $laundryPackage): JsonResponse
    {
        $payload = $request->validated();

        DB::transaction(function () use ($laundryPackage, $payload) {
            $laundryPackage->update($payload);
        });

        return (new LaundryPackageResource($laundryPackage->fresh('items')))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(LaundryPackage $laundryPackage): JsonResponse
    {
        // Jika butuh cek relasi sebelum delete, tambahkan logika di sini.
        $laundryPackage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
