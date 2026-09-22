<?php

namespace App\Actions\Inbound\Return;

use App\Models\Purchase\PurchaseReturn;

final class UpdateReturnTotal
{
    public function handle(PurchaseReturn $return): void
    {
        $total = $return->items()
            ->selectRaw('sum(total_amount) as total_amount')
            ->selectRaw('sum(total_qty) as total_qty')
            ->first();
        $return->update([
            'total' => $total->total_amount ?? 0,
            'total_qty' => $total->total_qty ?? 0,
            'total_amount' => ($total->total_amount ?? 0) - $return->discount + $return->expenses,
        ]);
    }
}
