<?php

namespace App\Exceptions;

use Exception;

/**
 * @phpstan-consistent-constructor
 */
class UnableToAllocateStockException extends Exception
{
    public static function unableToAllocate(string $productName, float $metersRequired, float $metersAllocated): static
    {
        return new static("Unable to allocate stock for product {$productName} with quantity {$metersRequired} and available stock {$metersAllocated}");
    }
}
