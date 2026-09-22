<?php

namespace App\Enums;

enum StoreTransferType: string
{
    case Store = 'store';
    case Return = 'return';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn (StoreTransferType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::Store => 'Transfer',
            self::Return => 'Return',
        };
    }
}
