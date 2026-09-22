<?php

namespace App\Http\Resources\Purchases;

use App\Models\Purchase\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PurchaseReturn $resource
 *
 * @mixin PurchaseReturn
 */
class PurchaseReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_date' => $this->transaction_display_date->toDateString(),
            'status' => $this->status->name,
            'can' => [
                'view' => $request->user()->can('view', $this->resource),
                'edit' => $request->user()->can('update', $this->resource),
                'unlock' => $request->user()->can('unlock', $this->resource),
                'ledger' => $request->user()->can('ledger', $this->resource),
                'inventory' => $request->user()->can('inventory', $this->resource),
            ],
            $this->merge(parent::toArray($request)),
        ];
    }
}
