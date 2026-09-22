<?php

namespace Database\Factories\Stock;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreTransferItem>
 */
class StoreTransferItemFactory extends Factory
{
    protected $model = StoreTransferItem::class;

    public function definition(): array
    {
        $unit = fake()->randomElement(PackingType::cases());
        $size = $unit === PackingType::Box ? 1 : fake()->numberBetween(1, 10);
        $qty = fake()->numberBetween(1, 10);
        $price = fake()->randomNumber(3);
        $expense = fake()->randomNumber(2);

        return [
            'store_transfer_id' => fn () => StoreTransfer::factory(),
            'product_id' => Product::factory(),
            'price' => $price,
            'expense' => $expense,
            'qty' => $qty,
            'size' => $size,
            'unit' => $unit,
            'total_qty' => $qty * $size,
            'total_amount' => $qty * $size * ($price + $expense),
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $price = $this->faker->randomNumber(4);
            $expense = $this->faker->randomNumber(2);
            $size = $this->faker->randomElement([5, 5, 6.5, 7, 7, 6]);

            return [
                'unit' => PackingType::Box,
                'total_qty' => $qty * $size,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'expense' => $expense,
                'total_amount' => $price * $qty + $expense * $qty,
            ];
        });
    }
}
