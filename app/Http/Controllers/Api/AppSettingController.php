<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    // ================= GET ALL SETTINGS =================
    public function index()
    {
        return AppSettingResource::collection(
            AppSetting::get()
        );
    }

    // ================= GET BY KEY =================
    public function show($key)
    {
        $setting = AppSetting::where('key', $key)->firstOrFail();

        return new AppSettingResource($setting);
    }

    // ================= CREATE / UPDATE =================
    public function store(Request $request)
    {
        $data = $request->validate([
            'key'   => 'required|string',
            'value' => 'nullable',
        ]);

        $setting = AppSetting::updateOrCreate(
            ['key' => $data['key']],
            ['value' => $data['value']]
        );

        return response()->json([
            'message' => 'Setting saved',
            'data'    => new AppSettingResource($setting),
        ]);
    }
}
