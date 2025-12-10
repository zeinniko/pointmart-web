<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'product_category_id' => 'sometimes|required|integer|exists:product_categories,id',
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'image'       => 'sometimes|nullable|string|max:255',
            'unit'        => 'sometimes|nullable|string|max:50',
            'barcode'     => 'sometimes|nullable|string|max:100',
            'is_active'   => 'sometimes|nullable|boolean',
        ];
    }
}
