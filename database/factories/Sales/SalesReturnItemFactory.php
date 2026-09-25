<?php

namespace Database\Factories\Sales;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesReturnItem>
 */
class SalesReturnItemFactory extends Factory
{
    protected $model = SalesReturnItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(1, 1, 10);
        $size = fake()->randomFloat(2, 1, 30);
        $rate = fake()->randomFloat(1, 100, 500);

        return [
            'sales_return_id' => fn () => SalesReturn::factory(),
            'product_id' => fn () => Product::factory(),
            'unit' => PackingType::Thaan,
            'size' => $size,
            'qty' => $qty,
            'total_qty' => $qty * $size,
            'rate' => $rate,
            'commission' => '',
            'total_commission' => 0,
            'total_amount' => $rate * $qty * $size,
        ];
    }

    public function box(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => PackingType::Box,
        ]);
    }

    public function suite(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => PackingType::Suit,
        ]);
    }
}
