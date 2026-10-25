<?php

namespace App\Http\Resources\Stock;

use App\Models\Stock\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockTransfer */
class StockTransferResource extends JsonResource
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
            'transfer_no' => $this->transfer_no,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_finish' => $this->product?->finish,
            'unit' => $this->unit->value,
            'size' => $this->size,
            'qty' => $this->qty,
            'meters' => $this->meters,
            'from_location' => $this->from_location->value,
            'from_location_label' => $this->from_location->label(),
            'to_location' => $this->to_location->value,
            'to_location_label' => $this->to_location->label(),
            'status' => $this->status->value,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'created_by' => $this->user?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'can_update' => $request->user()->can('update', $this->resource),
            'can_confirm' => $request->user()->can('confirm', $this->resource),
            'can_unlock' => $request->user()->can('unlock', $this->resource),
            'can_delete' => $request->user()->can('delete', $this->resource),
            'can_inventory' => $request->user()->can('inventory', $this->resource),
        ];
    }
}
