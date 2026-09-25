<?php

namespace App\Enums;

use App\Contracts\HasLabel;

enum JournalHead: string implements HasLabel
{
    use HasOptions;

    case Journal = 'journal';
    case Purchases = 'purchases';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Journal => 'Journal',
            self::Purchases => 'Purchases',
            self::Sales => 'Sales',
        };
    }
}
