<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;
use Illuminate\Support\Facades\DB;

class RecalculateStoreTransferTotals
{
    public function handle(StoreTransfer $transfer): void
    {
        $totals = StoreTransferItem::query()
            ->where('store_transfer_id', $transfer->id)
            ->select([
                DB::raw('sum(total_amount) as total'),
                DB::raw('sum(total_qty) as total_qty'),
            ])
            ->first();

        $total = $totals->total ?? 0;

        $transfer->update([
            'total' => $total,
            'total_qty' => $totals->total_qty ?? 0,
            'net_total' => $total + $transfer->expenses - $transfer->discount_on_total,
        ]);
    }
}
