<?php

namespace App\Enums;

use App\Contracts\HasLabel;
use BackedEnum;
use Illuminate\Support\Collection;
use UnitEnum;

trait HasOptions
{
    public static function toOptions(bool $all = true): Collection
    {
        return collect(self::cases())
            ->map(function (UnitEnum $row): array {
                $name = $row instanceof HasLabel ? $row->label() : $row->name;

                return [
                    'id' => $row instanceof BackedEnum ? $row->value : $row->name,
                    'name' => $name,
                ];
            });
    }

    public static function toValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toNames(): array
    {
        return array_column(self::cases(), 'name');
    }
}
