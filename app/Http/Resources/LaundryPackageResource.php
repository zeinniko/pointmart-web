<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaundryPackageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'price_per_kg' => $this->price_per_kg,
            'price_per_item' => $this->price_per_item,
            'min_kg' => $this->min_kg,
            'estimation_time' => $this->estimation_time,
            'included_addons' => $this->included_addons,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // relasi optional (hanya muncul jika controller memanggil ->load())
            'items' => LaundryItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}
