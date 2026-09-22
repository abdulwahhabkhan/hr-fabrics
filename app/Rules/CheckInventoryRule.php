<?php

namespace App\Rules;

use App\Repositories\InventoryRepository;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckInventoryRule implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $repo = resolve(InventoryRepository::class);
        $product_id = $this->data['product']['product_id'] ?? $this->data['item_id'];
        $name = $this->data['product']['name'] ?? 'Product';
        $unit = $this->data['unit'];
        $size = $this->data['size'];
        $qty = $this->data['qty'];
        $meters = $size > 0 ? $size * $qty : $qty;
        $stock = $repo->availableInventory($product_id, $unit, $size);
        $available_qty = (float) $stock->total_qty;
        $available_meters = (float) $stock->total_meters;

        if ($qty > $available_qty || $meters > $available_meters) {
            $fail("{$name} only have {$available_qty} available qty, {$available_meters} available meters");
        }

    }

    public function setData(array $data): self|static
    {
        $this->data = $data;

        return $this;
    }
}
