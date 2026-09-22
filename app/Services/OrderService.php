<?php

namespace App\Services;

use RoundingMode;

class OrderService
{
    public function getAgentCommission(array $data): float
    {
        $commission_rate = $data['commission'] ?? 0;
        if (! $commission_rate) {
            return 0;
        }

        if (str_contains($commission_rate, '%')) { // is percentage?
            $rate = str_replace('%', '', $commission_rate);
            $total_commission = (float) (($data['total_amount']) * ($rate / 100));
        } else {
            $total_commission = (float) $data['total_qty'] * (float) $commission_rate;
        }

        return round(num: $total_commission, precision: 2, mode: RoundingMode::HalfEven);
    }
}
