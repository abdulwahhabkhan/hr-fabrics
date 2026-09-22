<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Face = 'face';
    case FingerPrint = 'fingerprint';
    case Card = 'card';
    case Password = 'password';

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn (AttendanceMethod $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::Face => 'Face',
            self::FingerPrint => 'Fingerprint',
            self::Card => 'Card',
            self::Password => 'Password',
        };
    }

    public function rawVerify(): string
    {
        return match ($this) {
            self::Face => '15',
            self::FingerPrint => '1',
            self::Card => '4',
            self::Password => '3',
        };
    }
}
