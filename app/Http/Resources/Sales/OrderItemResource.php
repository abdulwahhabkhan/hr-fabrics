<?php

namespace App\Http\Resources\Sales;

use Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            $this->merge(
                Arr::except(
                    parent::toArray($request),
                    ['updated_at']
                )
            ),
        ];
    }
}
