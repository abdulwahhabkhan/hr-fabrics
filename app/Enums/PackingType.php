<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum PackingType: string
{
    use HasOptions;

    case Box = 'Box';
    case Suit = 'Suit';
    case Thaan = 'Thaan';

    public static function inboundUnits(): array
    {
        return [self::Box, self::Thaan];
    }
}
