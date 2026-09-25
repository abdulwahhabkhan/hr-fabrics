<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Present = 1;
    case Absent = 2;
    case Leave = 3;
    case HalfDay = 4;

    public static function toOptions(): array
    {
        return collect(self::cases())
            ->map(fn (AttendanceStatus $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Leave => 'Leave',
            self::HalfDay => 'Half Day',
        };
    }
}
