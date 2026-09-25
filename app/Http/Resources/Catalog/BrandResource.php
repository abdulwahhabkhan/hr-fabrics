<?php

namespace App\Http\Resources\Catalog;

use App\Http\Resources\UserResource;
use App\Models\Catalog\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Brand */
class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'user' => new UserResource($this->user),
            'updated_at' => $this->updated_at,
        ];
    }
}
