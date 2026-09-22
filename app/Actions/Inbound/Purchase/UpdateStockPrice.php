<?php

namespace App\Actions\Inbound\Purchase;

use App\Models\Purchase\Purchase;

class UpdateStockPrice
{
    public function handle(Purchase $receipt): void
    {
        foreach ($receipt->items as $item) {
            $query = $receipt->inventories()
                ->where('product_id', $item->product_id)
                ->where('unit', $item->unit);

            if ($item->isBox()) {
                $query->where('size', $item->size);
            }

            $query->update(['cost' => $item->price]);
        }

    }
}
