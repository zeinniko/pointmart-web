<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaundryPackageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|nullable|string|max:100',
            'category' => 'sometimes|nullable|string|max:100',
            'price_per_kg' => 'sometimes|nullable|numeric|min:0',
            'price_per_item' => 'sometimes|nullable|numeric|min:0',
            'min_kg' => 'sometimes|nullable|numeric|min:0',
            'estimation_time' => 'sometimes|nullable|string|max:255',
            'included_addons' => 'sometimes|nullable|array',
            'included_addons.*' => 'nullable',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}
