<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    /**
     * GET /user-devices
     * List device user login
     */
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar device user',
            'data' => UserDevice::where('user_id', $request->user()->id)->get()
        ]);
    }

    /**
     * DELETE /user-devices/{id}
     * Logout device tertentu
     */
    public function destroy(Request $request, string $id)
    {
        $device = UserDevice::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak ditemukan'
            ], 404);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device berhasil dihapus'
        ]);
    }
}
