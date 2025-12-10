<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'product_category_id' => 'required|integer|exists:product_categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'image'       => 'nullable|string|max:255',
            'unit'        => 'nullable|string|max:50',
            'barcode'     => 'nullable|string|max:100',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
