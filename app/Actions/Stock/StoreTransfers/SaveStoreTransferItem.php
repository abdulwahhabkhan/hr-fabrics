<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Enums\PackingType;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;

class SaveStoreTransferItem
{
    public function handle(array $data, StoreTransfer $transfer): StoreTransferItem
    {
        $item_id = $data['item_id'] ?? '';

        $data['product_id'] = $data['product']['product_id'];
        $data['total_qty'] = $data['qty'] * ($data['size'] ?? 1);
        $expense = (float) ($data['expense'] ?? 0);

        if ($data['unit'] === PackingType::Box->value) {
            $data['total_amount'] = $data['qty'] * $data['price'];
            $data['total_amount'] += $expense * $data['qty'];
        } else {
            $data['total_amount'] = $data['total_qty'] * $data['price'];
            $data['total_amount'] += $expense * $data['total_qty'];
        }

        unset($data['product'], $data['item_id']);

        if ($item_id) {
            $item = StoreTransferItem::find($item_id);
            $item->update($data);

            return $item;
        }

        return $transfer->items()->create($data);
    }
}
