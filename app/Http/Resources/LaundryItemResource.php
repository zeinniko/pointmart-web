<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaundryItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'package_id'    => $this->package_id,
            'item_name'     => $this->item_name,
            'price'         => $this->price,
            'category'      => $this->category,
            'image'         => $this->image,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->toDateTimeString(),
            'updated_at'    => $this->updated_at?->toDateTimeString(),

            // relasi optional — muncul jika ->load('package')
            'package' => new LaundryPackageResource(
                $this->whenLoaded('package')
            ),
        ];
    }
}
