<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaundryPackageRequest extends FormRequest
{
    public function authorize()
    {
        return true; // ganti dengan auth/gate bila perlu
    }

    public function rules()
    {
        return [
            // Mengikuti fillable yang kamu berikan (tanpa asumsi tambahan)
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'price_per_kg' => 'nullable|numeric|min:0',
            'price_per_item' => 'nullable|numeric|min:0',
            'min_kg' => 'nullable|numeric|min:0',
            'estimation_time' => 'nullable|string|max:255',
            'included_addons' => 'nullable|array',
            'included_addons.*' => 'nullable', // biarkan fleksibel (array of anything)
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
