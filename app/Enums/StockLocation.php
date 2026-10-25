<?php

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Traits\HasOptions;

enum StockLocation: string implements HasLabel
{
    use HasOptions;

    case Shop = 'shop';
    case WarehouseFSD = 'warehouse_fsd';

    public function label(): string
    {
        return match ($this) {
            self::Shop => 'Shop',
            self::WarehouseFSD => 'Warehouse FSD',
        };
    }
}
