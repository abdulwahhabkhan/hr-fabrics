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
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'from' => $this->from,
            'to' => $this->to,
            'from_meters' => $this->fromMeters(),
            'to_meters' => $this->toMeters(),
            'leftover_meters' => round($this->fromMeters() - $this->toMeters(), 2),
            'location' => $this->location->value,
            'location_label' => $this->location->label(),
            'transaction_date' => $this->transaction_date?->toDateString(),
            'created_by' => $this->user?->name,
            'updated_at' => $this->updated_at,
            'can_delete' => $request->user()->can('stocks.conversions.destroy'),
        ];
    }
}
