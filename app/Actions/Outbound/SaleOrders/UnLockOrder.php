<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Enums\OrderAllocation;
use App\Enums\OrderStatus;
use App\Models\Accounts\Journal;
use App\Models\Sales\Order;
use App\Models\User;
use DB;
use Illuminate\Support\Collection;
use Throwable;

final class UnLockOrder
{
    /**
     * @throws Throwable
     */
    public function handle(Order $order, User $user): void
    {
        resolve(RecordAction::class)->handle($order, $user, 'Sale Order UnLocked');

        DB::transaction(function () use ($order): void {
            /** @var Collection<int, Journal> $journals */
            $journals = $order->journal()->get();
            foreach ($journals as $journal) {
                $journal->transactions()->delete();
            }
            $order->journal()->delete();
            $order->inventories()->update([
                'outbound_type' => null,
                'outbound_id' => null,
                'outbound_item_id' => null,
                'outbound_on' => null,
            ]);
            $order->update(['status' => OrderStatus::Open, 'allocation' => OrderAllocation::Unallocated]);
        });

    }
}
