<?php

namespace App\Actions\Support;

use RoundingMode;

class CalculateAgentCommission
{
    public function handle(?float $commissionRate = null, float $totalAmount = 0, float $totalQty = 0): float|int
    {

        if (! $commissionRate) {
            return 0;
        }

        if (str_contains($commissionRate, '%')) { // is percentage?
            $rate = str_replace('%', '', $commissionRate);
            $total_commission = (float) (($totalAmount) * ($rate / 100));
        } else {
            $total_commission = (float) $totalQty * (float) $commissionRate;
        }

        return round(num: $total_commission, precision: 2, mode: RoundingMode::HalfEven);
    }
}
