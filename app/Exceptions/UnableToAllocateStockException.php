<?php

namespace App\Exceptions;

use Exception;

class UnableToAllocateStockException extends Exception
{
    public static function unableToAllocate(string $productName, int $metersRequired, int $metersAllocated): static
    {
        return new static("Unable to allocate stock for product {$productName} with quantity {$metersRequired} and available stock {$metersAllocated}");
    }
}
