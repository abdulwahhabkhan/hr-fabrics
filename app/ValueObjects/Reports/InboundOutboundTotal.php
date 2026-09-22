<?php

namespace App\ValueObjects\Reports;

class InboundOutboundTotal
{
    public function __construct(
        public float $totalQty,
    ) {}
}
