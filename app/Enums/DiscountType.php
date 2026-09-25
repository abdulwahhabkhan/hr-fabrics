<?php

namespace App\Enums;

enum DiscountType: string
{
    case PercentageOnTotal = 'percentage_on_total';
    case FixedPerMeter = 'fixed_per_meter';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn (DiscountType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::PercentageOnTotal => '% On Total',
            self::FixedPerMeter => 'Per Meter',
        };
    }

    public function valueLabel(int|float $value): string
    {
        return match ($this) {
            self::PercentageOnTotal => "{$value}% total",
            self::FixedPerMeter => "{$value}/meter",
        };
    }

    public function valueLabelShort(int|float $value): string
    {
        return match ($this) {
            self::PercentageOnTotal => "Discount {$value}%",
            self::FixedPerMeter => "Discount {$value}/m",
        };
    }
}
