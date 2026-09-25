<?php

namespace App\Http\Resources\Catalog;

use App\Http\Resources\UserResource;
use App\Models\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => $this->size,
            'description' => $this->description,
            'unit_price' => $this->unit_price,
            'cost' => $this->cost,
            'suit_price' => $this->suit_price,
            'finish' => $this->finish,
            'is_box' => $this->is_box,
            'user' => new UserResource($this->user),
            'brand' => new BrandShortResource($this->brand),
            'is_available' => $this->is_available,
            'updated_at' => $this->updated_at,
        ];
    }
}
