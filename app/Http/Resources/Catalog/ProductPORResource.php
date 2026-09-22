<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductPORResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $product_info = [
            $this->name,
            $this->finish,
        ];

        return [
            'product_id' => $this->id,
            'name' => $this->name,
            'is_box' => (int) $this->is_box,
            'unit_price' => $this->unit_price,
            'suit_price' => $this->suit_price,
            'finish' => $this->finish,
            'unit' => '',
            'size' => $this->size,
            'purchased_price' => $this->purchased_price,
            'brand_id' => $this->brand_id,
            'product_info' => implode(' ', $product_info),
        ];
    }
}
