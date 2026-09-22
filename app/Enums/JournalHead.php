<?php

namespace App\Enums;

enum JournalHead: string
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
