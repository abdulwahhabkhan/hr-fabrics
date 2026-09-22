<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'code' => $this->code,
            'price' => $this->price,
            'finish' => $this->finish,
            'unit' => $this->unit,
            'user' => new UserResource($this->user),
            'brand' => new BrandShortResource($this->brand),
            'updated_at' => $this->updated_at,
        ];
    }
}
