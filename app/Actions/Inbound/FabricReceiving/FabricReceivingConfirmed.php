<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Enums\Module;
use App\Enums\PackingType;
use App\Enums\TransactionType;
use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;
use DB;
use Throwable;

final class FabricReceivingConfirmed
{
    /**
     * @throws Throwable
     */
    public function handle(FabricReceiving $stock): void
    {
        if (! $stock->isClosed()) {
            return;
        }
        DB::transaction(function () use ($stock) {
            $items = $stock->items;
            $info = [
                'action' => 'add',
                'type' => TransactionType::Stock->value,
                'module' => Module::Stock->value,
                'invoice_no' => $stock->invoice_no,
            ];
            $stock->items()->update(['status' => 1]);
            $stock->inventories()->delete();
            $inventories = [];
            foreach ($items as $item) {
                $inventory = new Inventory();
                $inventory->stockable_item_id = $item->id;
                $inventory->product_id = $item->product_id;
                $inventory->size = $item->size;
                $inventory->unit = PackingType::from($item->unit);
                $inventory->qty = $item->qty;
                $inventory->meters = $item->total_qty;
                $inventory->info = $info;
                $inventory->transaction_date = $stock->transaction_date;
                $inventories[] = $inventory;

            }
            $stock->inventories()->saveMany($inventories);
        });
    }
}
