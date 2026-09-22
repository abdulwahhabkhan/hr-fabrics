<?php

namespace Database\Factories\Purchase;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PurchaseItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $unit = $this->faker->randomElement(PackingType::inboundUnits());

        $qty = $this->faker->numberBetween(1, 10);

        if ($unit === PackingType::Box) {
            $size = $this->faker->randomElement([5, 5.5, 6]);
            $total_qty = $qty;
            $price = $this->faker->numberBetween(2000, 5000);
        } else {
            $size = $this->faker->randomElement([18, 21, 27]);
            $total_qty = $qty * $size;
            $price = $this->faker->numberBetween(100, 500);
        }

        return [
            'purchase_id' => fn () => Purchase::inRandomOrder()->first()->id,
            'product_id' => fn () => Product::factory(),
            'voucher_no' => 'LN-'.$this->faker->randomNumber(3),
            'qty' => $qty,
            'size' => $size,
            'price' => $price,
            'unit' => $unit,
            'total_qty' => $total_qty,
            'total' => $total_qty * $price,
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $price = $this->faker->randomNumber(4);
            $size = $this->faker->randomElement([5, 5, 6.5, 7, 7, 6]);
            $total_qty = $qty;

            return [
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'total' => $price * $total_qty,
            ];
        });
    }

    public function thaan(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $price = $this->faker->randomNumber(3);
            $size = 0;
            $total_qty = $this->faker->randomNumber(4);

            return [
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'total' => $price * $total_qty,
            ];
        });
    }
}
