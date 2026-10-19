<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
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
            $this->releaseInventory($order);
            $order->update(['status' => OrderStatus::Open]);
        });

    }

    /**
     * Return issued stock to the inventory, merging lines that were split on issue back into their source line.
     */
    private function releaseInventory(Order $order): void
    {
        /** @var Inventory $issued */
        foreach ($order->inventories()->get() as $issued) {
            $source = Inventory::query()
                ->where('stockable_type', $issued->stockable_type)
                ->where('stockable_id', $issued->stockable_id)
                ->where('stockable_item_id', $issued->stockable_item_id)
                ->where('product_id', $issued->product_id)
                ->where('unit', $issued->unit)
                ->available()
                ->first();

            if ($source === null) {
                $issued->update([
                    'outbound_type' => null,
                    'outbound_id' => null,
                    'outbound_item_id' => null,
                    'outbound_on' => null,
                ]);

                continue;
            }

            $source->meters += $issued->meters;
            if ($issued->unit !== PackingType::Thaan) {
                $source->qty += $issued->qty;
            }
            $source->save();
            $issued->delete();
        }
    }
}
