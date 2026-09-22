<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public static function insufficientStock(string $productName, int $quantity, int $availableStock): static
    {
        return new static("Insufficient stock for product with quantity $quantity and available stock $availableStock");
    }

    public static function allocationException(int $modelId, float $required, float $remaining): static
    {
        return new static("Insufficient stock for product with modelId: $modelId, required -> $required and balance $remaining");
    }
}
