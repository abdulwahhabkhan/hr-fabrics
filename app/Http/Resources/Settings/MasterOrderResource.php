<?php

namespace App\Http\Resources\Settings;

use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A sale order with the meters each of its items needs and how many are booked against stock.
 *
 * @mixin Order
 *
 * @property Order $resource
 */
final class MasterOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allocate = Gate::forUser($request->user())->inspect('masterAllocate', $this->resource);
        $items = $this->resource->items()->with('product:id,name')->withSum('inventory', 'meters')->get();

        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'customer_name' => $this->customer_name,
            'net_total' => $this->net_total,
            'status' => $this->status,
            'allocation' => $this->allocation->value,
            'allocation_label' => $this->allocation->label(),
            'transaction_date' => $this->transaction_date?->toDateString(),
            'required_meters' => round((float) $items->sum('total_qty'), 2),
            'allocated_meters' => round((float) $items->sum('inventory_sum_meters'), 2),
            'items' => $items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_name' => $item->product?->name,
                'unit' => $item->unit,
                'location' => $item->location->label(),
                'size' => $item->size,
                'qty' => $item->qty,
                'meters' => $item->total_qty,
                'allocated_meters' => round((float) $item->inventory_sum_meters, 2),
            ]),
            'can_allocate' => $allocate->allowed(),
            'allocate_blocked_reason' => $allocate->denied() ? $allocate->message() : null,
        ];
    }
}
