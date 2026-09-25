<?php

namespace App\Enums;

enum DirectoryType: string
{
    case SalesBilties = 'sales.bilties';
    case FabricsReceivings = 'fabrics.receivings';

    case Vouchers = 'vouchers';

    public function folderPath(): string
    {
        return match ($this) {
            self::SalesBilties => 'sales/bilties/',
            self::FabricsReceivings => 'inbound/fabric-receivings/',
            self::Vouchers => 'vouchers/',
        };
    }
}
