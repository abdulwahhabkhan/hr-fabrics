<?php

namespace App\Actions\Inbound\Purchase;

use App\Actions\Stock\Conversions\CostConversion;
use App\Models\Purchase\Purchase;
use App\Models\Stock\Conversion;

class UpdateStockPrice
{
    public function __construct(private readonly CostConversion $costConversion) {}

    /**
     * Price the received stock from the voucher, then re-price the stock any conversion has already cut from it.
     */
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

        $conversionIds = $receipt->inventories()
            ->where('outbound_type', Conversion::morphClass())
            ->distinct()
            ->pluck('outbound_id');

        Conversion::query()
            ->whereKey($conversionIds)
            ->get()
            ->each(fn (Conversion $conversion) => $this->costConversion->handle($conversion));
    }
}
