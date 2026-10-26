<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Enums\OrderAllocation;
use App\Models\Sales\Order;

final class SyncOrderAllocation
{
    /**
     * Set the order's allocation from the stock meters booked to it against the meters its items need.
     */
    public function handle(Order $order): void
    {
        $order->update([
            'allocation' => OrderAllocation::fromMeters(
                (float) $order->inventories()->sum('meters'),
                (float) $order->items()->sum('total_qty'),
            ),
        ]);
    }
}
