<?php

namespace App\Rules;

use App\Enums\PackingType;
use App\Services\InventoryService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckInventoryRule implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = resolve(InventoryService::class);
        $product_id = $this->data['product']['product_id'] ?? $this->data['item_id'];
        $name = $this->data['product']['name'] ?? 'Product';
        $unit = PackingType::from($this->data['unit']);
        $size = $this->data['size'];
        $qty = $this->data['qty'];
        $meters = $size > 0 ? $size * $qty : $qty;
        $stock = $service->availableInventory($product_id, $unit, $size);
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
