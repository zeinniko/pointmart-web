<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->id,
            'product_category_id'  => $this->product_category_id,
            'name'                 => $this->name,
            'description'          => $this->description,
            'price'                => $this->price,
            'image'                => $this->image,
            'unit'                 => $this->unit,
            'barcode'              => $this->barcode,
            'is_active'            => $this->is_active,
            'created_at'           => $this->created_at?->toDateTimeString(),
            'updated_at'           => $this->updated_at?->toDateTimeString(),

            // relasi optional
            'category' => new ProductCategoryResource(
                $this->whenLoaded('category')
            ),
            'stock' => new ProductStockResource(
                $this->whenLoaded('stock')
            ),
        ];
    }
}
