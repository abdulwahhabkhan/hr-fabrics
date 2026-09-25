<?php

namespace App\Actions\Support;

use RoundingMode;

class CalculateAgentCommission
{
    public function handle(float|string|null $commissionRate = null, float $totalAmount = 0, float $totalQty = 0): float|int
    {

        if (! $commissionRate) {
            return 0;
        }

        if (str_contains((string) $commissionRate, '%')) { // is percentage?
            $rate = (float) str_replace('%', '', (string) $commissionRate);
            $total_commission = (float) (($totalAmount) * ($rate / 100));
        } else {
            $total_commission = (float) $totalQty * (float) $commissionRate;
        }

        return round(num: $total_commission, precision: 2, mode: RoundingMode::HalfEven);
    }
}
