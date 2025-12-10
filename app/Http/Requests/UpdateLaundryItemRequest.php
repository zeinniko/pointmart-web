<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaundryItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'package_id' => 'sometimes|required|integer|exists:laundry_packages,id',
            'item_name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|nullable|numeric|min:0',
            'category' => 'sometimes|nullable|string|max:100',
            'image' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}
