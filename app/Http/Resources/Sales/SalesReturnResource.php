<?php

namespace App\Http\Resources\Sales;

use App\Models\Sales\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalesReturn
 *
 * @property SalesReturn $resource
 */
class SalesReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /*if ($this->status === ReturnStatus::Open) {
            $can_edit = true;
            $can_unlock = false;
        } else {
            $can_edit = false;
            $can_unlock = $this->updated_at->isSameDay(now());
        }*/

        return [
            'status' => $this->status->name,
            'transaction_date' => $this->transaction_display_date->toDateString(),
            'can_edit' => $request->user()->can('update', $this->resource),
            'can_unlock' => $request->user()->can('unlock', $this->resource),
            'can_inventory' => $request->user()->can('inventory', $this->resource),
            'can_ledger' => $request->user()->can('ledger', $this->resource),
            $this->merge(parent::toArray($request)),
        ];
    }
}
