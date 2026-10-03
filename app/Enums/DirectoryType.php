<?php

namespace App\Enums;

enum DirectoryType: string
{
    case SalesBilties = 'sales.bilties';
    case FabricsReceivings = 'fabrics.receivings';
    case SalesReturns = 'sales.returns';

    case Vouchers = 'vouchers';

    public function folderPath(): string
    {
        return match ($this) {
            self::SalesBilties => 'sales/bilties/',
            self::FabricsReceivings => 'inbound/fabric-receivings/',
            self::SalesReturns => 'sales/returns/',
            self::Vouchers => 'vouchers/',
        };
    }
}
