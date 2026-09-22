<?php

namespace App\Http\Resources\Purchases;

use App\Models\Purchase\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Purchase
 * @property Purchase $resource
 * */
final class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            /*'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'bill_no' => $this->bill_no,
            'bilti_no' => $this->bilti_no,
            'lot_no' => $this->lot_no,
            'total' => $this->total,
            'discount' => $this->discount,
            'total_qty' => $this->total_qty,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'supplier_name' => $this->supplier_name,
            'supplier_id' => $this->supplier_id,*/
            'can' => [
                'view' => $request->user()->can('view', $this->resource),
                'edit' => $request->user()->can('update', $this->resource),
                'unlock' => $request->user()->can('unlock', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'inventory' => $request->user()->can('inventory', $this->resource),
                'ledger' => $request->user()->can('ledger', $this->resource),
            ],
            'transaction_date' => $this->transaction_display_date->toDateString(),
            $this->merge(parent::toArray($request)),
        ];
    }
}
