<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function image(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'folder' => 'nullable|string'
        ]);

        // folder default
        $folder = $request->input('folder', 'general');

        // contoh hasil: products/2026/01
        $path = $folder . '/' . date('Y/m');

        $file = $request->file('image');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $storedPath = $file->storeAs(
            $path,
            $filename,
            'public'
        );

        return response()->json([
            'url' => asset('storage/' . $storedPath),
            'path' => $storedPath
        ]);
    }
}
