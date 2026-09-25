<?php

namespace App\Http\Resources\Reports;

use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FabricReceiving
 *
 * @property-read int $product_id
 * @property-read string $product_name
 * @property-read string $finish
 * @property-read string $unit
 * @property-read float $size
 * @property-read int $qty
 */
class PurchasesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // return parent::toArray($request);
        return [
            'po_id' => $this->id,
            'item_id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'bilti_no' => $this->bilti_no,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'finish' => $this->finish,
            'type' => $this->unit,
            'size' => $this->size,
            'qty' => $this->qty,
            'total_qty' => $this->total_qty,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
