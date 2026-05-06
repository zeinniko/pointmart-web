<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserAddressController extends Controller
{
    /**
     * GET /user-addresses
     * List alamat user login
     */
    public function index(Request $request)
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar alamat pengguna',
            'data' => $addresses
        ]);
    }

    /**
     * POST /user-addresses
     * Tambah alamat baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label'     => 'required|string|max:50',
            'address'   => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $isDefault = $request->boolean('is_default');

        $address = DB::transaction(function () use ($user, $request, $isDefault) {

            UserAddress::where('user_id', $user->id)->lockForUpdate()->get();

            // Jika set default → reset semua
            if ($isDefault) {
                UserAddress::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            // Jika belum ada default sama sekali → paksa jadi default
            $hasDefault = UserAddress::where('user_id', $user->id)
                ->where('is_default', true)
                ->exists();

            $finalDefault = $isDefault || !$hasDefault;

            return UserAddress::create([
                'user_id'    => $user->id,
                'label'      => $request->label,
                'address'    => $request->address,
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'is_default' => $finalDefault,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil ditambahkan',
            'data'    => $address
        ], 201);
    }


    /**
     * GET /user-addresses/{id}
     */
    public function show(Request $request, string $id)
    {
        $address = UserAddress::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail alamat',
            'data'    => $address
        ]);
    }

    /**
     * PUT /user-addresses/{id}
     */
    public function update(Request $request, string $id)
    {
        $address = UserAddress::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'label'     => 'sometimes|required|string|max:50',
            'address'   => 'sometimes|required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $isDefault = $request->boolean('is_default');

        $address = DB::transaction(function () use ($request, $user, $address, $isDefault) {

            UserAddress::where('user_id', $user->id)->lockForUpdate()->get();

            // Jika set default → reset semua
            if ($request->has('is_default') && $isDefault) {
                UserAddress::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            // Update data
            $address->update($request->only([
                'label',
                'address',
                'latitude',
                'longitude',
                'is_default'
            ]));

            $hasDefault = UserAddress::where('user_id', $user->id)
                ->where('is_default', true)
                ->exists();

            if (!$hasDefault) {
                $address->update(['is_default' => true]);
            }

            return $address;
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil diperbarui',
            'data'    => $address
        ]);
    }


    /**
     * DELETE /user-addresses/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $address = UserAddress::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        DB::transaction(function () use ($user, $address) {

            UserAddress::where('user_id', $user->id)->lockForUpdate()->get();

            $wasDefault = $address->is_default;

            $address->delete();

            // Jika yang dihapus default → pilih 1 jadi default
            if ($wasDefault) {
                $next = UserAddress::where('user_id', $user->id)->first();

                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dihapus'
        ]);
    }
}
