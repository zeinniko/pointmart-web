<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'icon'       => $this->icon,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // relasi optional (tidak dipanggil kecuali ->load('products'))
            'products'   => ProductResource::collection(
                $this->whenLoaded('products')
            ),
        ];
    }
}
