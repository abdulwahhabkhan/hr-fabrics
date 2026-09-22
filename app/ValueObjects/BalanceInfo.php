<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class BalanceInfo implements Arrayable
{
    public function __construct(public float $total_credit, public float $total_debit) {}

    public function balance(bool $rounded = true): float
    {
        if ($rounded) {
            return round($this->total_debit - $this->total_credit);
        }

        return $this->total_debit - $this->total_credit;
    }

    public function toArray(): array
    {
        return [
            'total_credit' => $this->total_credit,
            'total_debit' => $this->total_debit,
            'balance' => $this->balance(),
        ];
    }
}
