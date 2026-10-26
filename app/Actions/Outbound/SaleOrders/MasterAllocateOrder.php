<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Sales\Order;
use App\Models\User;
use DB;
use Throwable;

final class MasterAllocateOrder
{
    /**
     * Book stock for a confirmed order that is not fully allocated. Lines already booked to it are freed first so
     * every item is booked again first-in first-out, then the order's allocation is recorded. Nothing changes when
     * any item is short of stock.
     *
     * @throws UnableToAllocateStockException
     * @throws Throwable
     */
    public function handle(Order $order, User $user): void
    {
        DB::transaction(function () use ($order, $user): void {
            resolve(RecordAction::class)->handle($order, $user, 'SO allocated by master user');

            $order->inventories()->update([
                'outbound_type' => null,
                'outbound_id' => null,
                'outbound_item_id' => null,
                'outbound_on' => null,
            ]);

            resolve(IssueOrderInventory::class)->handle($order->load('items'));
        });
    }
}
