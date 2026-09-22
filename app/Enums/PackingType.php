<?php

namespace App\Enums;

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
