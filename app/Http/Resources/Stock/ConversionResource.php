<?php

namespace App\Http\Resources\Stock;

use App\Models\Stock\Conversion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversion
 */
class ConversionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'user' => $this->user->name,
            'sku' => $this->sku,
            'from' => $this->from,
            'to' => $this->to,
            'updated_at' => $this->updated_at,
        ];
    }
}
