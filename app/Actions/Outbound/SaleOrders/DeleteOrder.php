<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Accounts\Journal;
use App\Models\Sales\Order;
use App\Models\User;
use DB;
use Illuminate\Support\Collection;
use Throwable;

final class DeleteOrder
{
    /**
     * Delete an open order with its items, journal entries and release any inventory still allocated to it.
     *
     * @throws Throwable
     */
    public function handle(Order $order, User $user): void
    {
        if ($order->isClosed()) {
            throw ActionNotAllowedException::actionNotAllowed('Delete Order for Closed order '.$order->id);
        }

        DB::transaction(function () use ($order, $user): void {
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
            $order->items()->delete();
            $order->delete();
            resolve(RecordAction::class)->handle($order, $user, 'Sale Order Deleted');
        });
    }
}
