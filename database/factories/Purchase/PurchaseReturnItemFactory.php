<?php

namespace Database\Factories\Purchase;

use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnItem>
 */
class PurchaseReturnItemFactory extends Factory
{
    protected $model = PurchaseReturnItem::class;

    public function definition(): array
    {
        $unit = fake()->randomElement(['Box', 'Thaan']);
        $qty = fake()->numberBetween(1, 10);

        if ($unit === 'Box') {
            $size = fake()->randomElement([5, 5.5, 6]);
            $total_qty = $qty;
            $price = fake()->numberBetween(2000, 5000);
        } else {
            $size = fake()->randomElement([18, 21, 27]);
            $total_qty = $qty * $size;
            $price = fake()->numberBetween(100, 500);
        }

        return [
            'qty' => $qty,
            'size' => $size,
            'rate' => $price,
            'unit' => $unit,
            'total_qty' => $total_qty,
            'total_amount' => $total_qty * $price,
            'purchase_return_id' => fn () => PurchaseReturn::factory(),
            'product_id' => fn () => Product::factory(),
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            $qty = fake()->randomNumber(2);
            $price = fake()->randomNumber(4);
            $size = fake()->randomElement([5, 5, 6.5, 7, 7, 6]);
            $total_qty = $qty;

            return [
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'rate' => $price,
                'total' => $price * $total_qty,
            ];
        });
    }

    public function thaan(): self
    {
        return $this->state(function () {
            $qty = fake()->randomNumber(2);
            $price = fake()->randomNumber(3);
            $size = 0;
            $total_qty = fake()->randomNumber(4);

            return [
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'rate' => $price,
                'total' => $price * $total_qty,
            ];
        });
    }
}
