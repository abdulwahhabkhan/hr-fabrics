<?php

namespace App\Actions\Stock\Conversions;

use App\Models\Stock\Conversion;

class CostConversion
{
    /**
     * Price the produced lots at the meter-weighted cost of the thaan lines the conversion consumed.
     * Lots stay unpriced while none of the consumed lines has a cost yet.
     */
    public function handle(Conversion $conversion): void
    {
        $consumed = $conversion->consumedInventories()
            ->whereNotNull('cost')
            ->selectRaw('COALESCE(SUM(meters), 0) as total_meters, COALESCE(SUM(meters * cost), 0) as total_value')
            ->toBase()
            ->first();

        $meters = (float) ($consumed->total_meters ?? 0);
        $cost = $meters > 0 ? round((float) $consumed->total_value / $meters, 2) : null;

        $conversion->producedInventories()->update(['cost' => $cost]);
    }
}
