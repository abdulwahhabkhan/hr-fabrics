<?php

namespace App\Enums;

use Illuminate\Support\Collection;

trait HasOptions
{
    public static function toOptions($all = true): Collection
    {
        return collect(self::cases())
            ->when(! $all, function () {})
            ->map(function ($row) {
                $name = $row->name;
                if (method_exists($row, 'label')) {
                    $name = $row->label();
                }

                return [
                    'id' => $row->value ?? $row->name,
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
