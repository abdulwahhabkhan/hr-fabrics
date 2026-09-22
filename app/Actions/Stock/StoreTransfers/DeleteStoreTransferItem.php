<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Enums\StoreTransferType;
use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteStoreTransferItem
{
    /**
     * @throws Throwable
     */
    public function handle(int $itemId): StoreTransfer
    {
        $item = StoreTransferItem::findOrFail($itemId);
        $transfer = $item->storeTransfer;

        DB::transaction(function () use ($item, $transfer) {
            if ($transfer->type === StoreTransferType::Store->value) {
                Inventory::query()
                    ->where('outbound_type', StoreTransfer::morphClass())
                    ->where('outbound_item_id', $item->id)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (Inventory $inventory) {
                        $inventory->outbound_id = null;
                        $inventory->outbound_type = null;
                        $inventory->outbound_item_id = null;
                        $inventory->save();
                    });
            }

            $item->delete();
        });

        return $transfer;
    }
}
