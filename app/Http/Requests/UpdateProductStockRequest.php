<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStockRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'stock'      => 'sometimes|required|integer|min:0',
            'min_stock'  => 'sometimes|nullable|integer|min:0',
            'updated_by' => 'sometimes|nullable|integer|exists:users,id'
        ];
    }
}
