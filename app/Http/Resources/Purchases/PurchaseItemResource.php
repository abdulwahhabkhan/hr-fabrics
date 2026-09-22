<?php

namespace App\Http\Resources\Purchases;

use App\Models\Purchase\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseItem
 * @property mixed $product_name
 */
final class PurchaseItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'purchase_id' => $this->purchase_id,
            'voucher_no' => $this->voucher_no,
            'unit' => $this->unit,
            'product_name' => $this->product_name,
            'qty' => $this->qty,
            'price' => $this->price,
            'size' => $this->size,
            'total_qty' => $this->total_qty,
            'total' => $this->total,
            'product' => $this->product_id,
        ];
    }
}
