<?php

namespace App\Http\Resources\Purchases;

use App\Models\Purchase\FabricReceivingItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FabricReceivingItem
 */
final class FabricReceivingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'fabric_receiving_id' => $this->fabric_receiving_id,
            'voucher_no' => $this->voucher_no,
            'unit' => $this->unit,
            'product_name' => $this->product_name,
            'qty' => $this->qty,
            'size' => $this->size,
            'total_qty' => $this->total_qty,
            'product' => $this->product_id,
        ];
    }
}
