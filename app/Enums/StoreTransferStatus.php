<?php

namespace App\Enums;

enum StoreTransferStatus: int
{
    case Open = 0;
    case Closed = 1;

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return array_column(self::cases(), 'name');
    }
}
