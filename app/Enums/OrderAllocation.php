<?php

namespace App\Enums;

use App\Contracts\HasLabel;
use App\Traits\HasOptions;

/**
 * How much of a sale order's meters are booked against stock.
 */
enum OrderAllocation: int implements HasLabel
{
    use HasOptions;

    case Unallocated = 0;
    case PartiallyAllocated = 1;
    case Allocated = 2;

    /**
     * Allocation for the meters booked against the meters the order needs.
     */
    public static function fromMeters(float $allocated, float $required): self
    {
        $allocated = round($allocated, 2);

        return match (true) {
            $allocated <= 0 => self::Unallocated,
            $allocated >= round($required, 2) => self::Allocated,
            default => self::PartiallyAllocated,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unallocated => 'Unallocated',
            self::PartiallyAllocated => 'Partially allocated',
            self::Allocated => 'Allocated',
        };
    }
}
