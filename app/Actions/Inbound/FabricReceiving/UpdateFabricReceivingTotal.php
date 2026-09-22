<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Models\Purchase\FabricReceiving;
use Illuminate\Support\Facades\DB;

class UpdateFabricReceivingTotal
{
    public function handle(FabricReceiving $stock): void
    {
        $total = $stock->items()
            ->select([
                DB::raw('sum(total_qty) as total_meters'),
                DB::raw('sum(qty) as total_qty'),
            ])
            ->first();
        $stock->update([
            'total_qty' => $total->total_qty ?? 0,
            'total_meters' => $total->total_meters ?? 0,
        ]);
    }
}
