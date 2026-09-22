<?php

namespace App\Http\Resources\Stock;

use Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreTransferItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            $this->merge(
                Arr::except(parent::toArray($request), ['updated_at'])
            ),
        ];
    }
}
