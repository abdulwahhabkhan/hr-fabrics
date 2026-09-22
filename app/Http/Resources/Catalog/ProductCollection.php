<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @see Product */
class ProductCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
