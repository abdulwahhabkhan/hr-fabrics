<?php

namespace App\Http\Resources\Sales;

use App\Models\Sales\Order;
use Arr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order
 * @property Order $resource
 * */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // $array = parent::toArray($request);
        // $array['can_unlock'] = Carbon::now()->format('Y-m-d') === Carbon::parse($array['updated_at'])->format('Y-m-d');

        return [
            'transaction_date' => $this->transaction_display_date->toDateString(),
            'from_shop' => $this->from_shop,
            'bilti' => $this->has_bilti,
            'can_unlock' => $request->user()->can('unlock', $this->resource),
            'can_ledger' => $request->user()->can('ledger', $this->resource),
            'can_inventory' => $request->user()->can('inventory', $this->resource),
            'can_update' => $request->user()->can('update', $this->resource),
            'can_view' => $request->user()->can('view', $this->resource),
            'can_bilti' => $request->user()->can('bilti', $this->resource),
            'can_gate_pass' => $request->user()->can('gate-pass', $this->resource),
            $this->merge(parent::toArray($request)),
        ];
        /*return [
            $this->merge(
                Arr::only(
                    $array,
                    [
                        'id',
                        'invoice_no',
                        'total_qty',
                        'net_total',
                        'paid',
                        'bilti',
                        'purchase_type',
                        'status',
                        'customer_id',
                        'customer_name',
                        'city',
                        'from_shop',
                        'updated_at',
                        'confirmed_at',
                        'created_at',
                        'can_unlock',
                    ]
                )
            ),
        ];*/
    }
}
