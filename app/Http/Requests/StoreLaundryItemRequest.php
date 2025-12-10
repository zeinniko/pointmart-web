<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaundryItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'package_id' => 'required|integer|exists:laundry_packages,id',
            'item_name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
