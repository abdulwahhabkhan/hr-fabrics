<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\Inventory\IssueInventory;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Sales\Order;

final class IssueOrderInventory
{
    /**
     * Book stock for every order item, dated on the order's transaction date.
     *
     * @throws UnableToAllocateStockException
     */
    public function handle(Order $order): void
    {
        foreach ($order->items as $item) {
            resolve(IssueInventory::class)
                ->setOutbound($order)
                ->setTransactionDate($order->transaction_date)
                ->setOutboundItemId($item->id)
                ->setProductId($item->product_id)
                ->setSize($item->size)
                ->setQuantity($item->qty)
                ->setUnit($item->unit)
                ->setLocation($item->location)
                ->setMeters($item->total_qty)
                ->issue();
        }
    }
}
