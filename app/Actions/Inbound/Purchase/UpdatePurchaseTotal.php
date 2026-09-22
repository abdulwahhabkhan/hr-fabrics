<?php

namespace App\Actions\Inbound\Purchase;

use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use Illuminate\Support\Facades\DB;

final class UpdatePurchaseTotal
{
    public function handle(Purchase $receipt): void
    {
        $total = PurchaseItem::query()->where('purchase_id', $receipt->id)
            ->sum(DB::raw('total'));
        $receipt->update([
            'total' => $total,
        ]);
    }
}
