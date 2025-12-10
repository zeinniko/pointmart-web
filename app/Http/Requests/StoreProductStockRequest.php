<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductStockRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'stock'      => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'updated_by' => 'nullable|integer|exists:users,id'
        ];
    }
}
