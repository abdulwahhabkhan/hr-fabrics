<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Models\Sales\Order;

class UpdateOrderTotal
{
    public function handle(Order $order): void
    {
        $total = $order->items()
            ->selectRaw('sum(total_amount) as total')
            ->selectRaw('sum(discount) as discount')
            ->selectRaw('sum(total_qty) as total_qty')
            ->selectRaw('sum(total_commission) as total_commission')
            ->first();
        $commission = (int) $total->total_commission;
        $net_total = $total->total - $total->discount + $order->expenses - $order->discount_on_total;
        $order->update([
            'total' => $total->total,
            'customer_discount' => $total->discount,
            'total_qty' => $total->total_qty,
            'net_total' => $net_total,
            'commission' => $commission,
        ]);
    }
}
