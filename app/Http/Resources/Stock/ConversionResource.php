<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_id' => $this->receipt_id,
            // 'user' => $this->user->name,
            'lot_no' => $this->lot_no,
            'sku' => $this->sku,
            'from' => $this->from,
            'to' => $this->to,
            'updated_at' => $this->updated_at,
        ];
    }
}
