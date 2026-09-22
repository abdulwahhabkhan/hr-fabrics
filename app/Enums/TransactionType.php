<?php

namespace App\Enums;

enum TransactionType: string
{
    use HasOptions;

    case Stock = 'GRN';
    case StockReturn = 'RTV';

    case Purchase = 'ASN';
    case SaleOrder = 'SO';
    case SaleOrderReturn = 'SOR';

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Goods Receipt Note',
            self::StockReturn => 'Return to Vendor',
            self::Purchase => 'Purchase',
            self::SaleOrder => 'Sale Order',
            self::SaleOrderReturn => 'Sale Order Return',
        };
    }
}
