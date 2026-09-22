<?php

namespace App\Http\Resources\Stock;

use App\Models\Stock\StoreTransfer;
use Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StoreTransfer */
class StoreTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $array = parent::toArray($request);
        $array['confirmed_at'] ??= $array['created_at'];
        $array['can_view'] = $request->user()->can('view', $this->resource);
        $array['can_update'] = $request->user()->can('update', $this->resource);
        $array['can_unlock'] = $request->user()->can('unlock', $this->resource);
        $array['can_ledger'] = $request->user()->can('ledger', $this->resource);
        $array['can_inventory'] = $request->user()->can('inventory', $this->resource);

        return [
            $this->merge(
                Arr::only(
                    $array,
                    [
                        'id',
                        'transfer_no',
                        'total',
                        'net_total',
                        'total_qty',
                        'type',
                        'status',
                        'account_id',
                        'account_name',
                        'city',
                        'updated_at',
                        'confirmed_at',
                        'created_at',
                        'can_view',
                        'can_update',
                        'can_unlock',
                        'can_ledger',
                        'can_inventory',
                    ]
                )
            ),
        ];
    }
}
