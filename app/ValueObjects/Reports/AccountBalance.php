<?php

namespace App\ValueObjects\Reports;

class AccountBalance
{
    public function __construct(
        public int $accountId,
        public string $accountName,
        public ?string $city,
        public float $openingBalance,
        public float $closingBalance,
        public float $debit,
        public float $credit
    ) {}
}
