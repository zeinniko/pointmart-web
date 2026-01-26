<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        Log::info('[ADDRESS][STORE] Request masuk', [
            'user_id' => optional($request->user())->id,
            'payload' => $request->all(),
        ]);
    
        $validator = Validator::make($request->all(), [
            'label'     => 'required|string|max:50',
            'address'   => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default'=> 'nullable|boolean',
        ]);
    
        if ($validator->fails()) {
            Log::warning('[ADDRESS][STORE] Validasi gagal', [
                'errors' => $validator->errors(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }
    
        $user = $request->user();
    
        if ($request->is_default) {
            Log::info('[ADDRESS][STORE] Set default → reset alamat lain', [
                'user_id' => $user->id,
            ]);
    
            UserAddress::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }
    
        $address = UserAddress::create([
            'user_id'    => $user->id,
            'label'      => $request->label,
            'address'    => $request->address,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'is_default' => $request->is_default ?? false,
        ]);
    
        Log::info('[ADDRESS][STORE] Alamat berhasil dibuat', [
            'address_id' => $address->id,
            'user_id'    => $user->id,
        ]);
    
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
        Log::info('[ADDRESS][UPDATE] Request masuk', [
            'address_id' => $id,
            'user_id'    => optional($request->user())->id,
            'payload'    => $request->all(),
        ]);
    
        $address = UserAddress::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
    
        if (!$address) {
            Log::warning('[ADDRESS][UPDATE] Alamat tidak ditemukan', [
                'address_id' => $id,
                'user_id'    => $request->user()->id,
            ]);
    
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
            'is_default'=> 'nullable|boolean',
        ]);
    
        if ($validator->fails()) {
            Log::warning('[ADDRESS][UPDATE] Validasi gagal', [
                'errors' => $validator->errors(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }
    
        if ($request->has('is_default') && $request->boolean('is_default')) {
            Log::info('[ADDRESS][UPDATE] Set default → reset alamat lain', [
                'user_id' => $request->user()->id,
            ]);
        
            UserAddress::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }
    
        $address->update($request->only([
            'label',
            'address',
            'latitude',
            'longitude',
            'is_default'
        ]));
    
        Log::info('[ADDRESS][UPDATE] Alamat berhasil diperbarui', [
            'address_id' => $address->id,
            'user_id'    => $request->user()->id,
        ]);
    
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
        $address = UserAddress::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan'
            ], 404);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dihapus'
        ]);
    }
}
