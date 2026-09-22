<?php

namespace App\Enums;

enum OrderStatus: int
{
    case Open = 0;
    case Close = 1;

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return array_column(self::cases(), 'name');
    }
}
