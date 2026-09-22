<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Enums\OrderStatus;
use App\Models\Sales\Order;
use App\Models\User;
use DB;
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
            $journals = $order->journal()->get();
            foreach ($journals as $journal) {
                $journal->transactions()->delete();
            }
            $order->journal()->delete();
            $order->inventories()->delete();
            $order->update(['status' => OrderStatus::Open]);
        });

    }
}
