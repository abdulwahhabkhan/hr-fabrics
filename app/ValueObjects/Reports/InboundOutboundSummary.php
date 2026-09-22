<?php

namespace App\ValueObjects\Reports;

class InboundOutboundSummary
{
    public function __construct(
        public float $totalQty,
    ) {}
}
