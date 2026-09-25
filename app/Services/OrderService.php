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

        if (str_contains((string) $commission_rate, '%')) { // is percentage?
            $rate = (float) str_replace('%', '', (string) $commission_rate);
            $total_commission = (float) (($data['total_amount']) * ($rate / 100));
        } else {
            $total_commission = (float) $data['total_qty'] * (float) $commission_rate;
        }

        return round(num: $total_commission, precision: 2, mode: RoundingMode::HalfEven);
    }
}
