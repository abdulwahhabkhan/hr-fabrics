<?php

namespace Database\Factories\Sales;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $unit = $this->faker->randomElement(PackingType::cases());
        $size = $unit === PackingType::Box ? 1 : $this->faker->numberBetween(1, 10);
        $qty = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomNumber(3);

        return [
            'order_id' => function () {
                return Order::inRandomOrder()->first()->id;
            },
            'product_id' => fn () => Product::factory(),
            'price' => $price,
            'qty' => $qty,
            'size' => $size,
            'unit' => $unit,
            'discount' => 0,
            'total_qty' => $qty * $size,
            'total_amount' => $qty * $size * $price,
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $price = $this->faker->randomNumber(4);
            $size = $this->faker->randomElement([5, 5, 6.5, 7, 7, 6]);
            $total_qty = $qty * $size;

            return [
                'unit' => PackingType::Box,
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'total_amount' => $price * $qty,
            ];
        });
    }

    public function suit(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $price = $this->faker->numberBetween(500, 1500);
            $size = $this->faker->randomElement([5.5, 6, 5.5, 6, 6, 6]);
            $total_qty = $qty * $size;

            return [
                'unit' => PackingType::Suit,
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'total_amount' => $price * $total_qty,
            ];
        });
    }

    public function thaan(): self
    {
        return $this->state(function () {
            $qty = $this->faker->numberBetween(1, 5);
            $price = $this->faker->numberBetween(100, 300);
            $size = $this->faker->randomElement([21, 25, 27, 25, 27, 27]);
            $total_qty = $qty * $size;

            return [
                'unit' => PackingType::Thaan,
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'total_amount' => $price * $total_qty,
            ];
        });
    }

    public function commission($commission = 0): self
    {
        return $this->state(function () use ($commission) {
            $qty = $this->faker->numberBetween(1, 5);
            $price = $this->faker->numberBetween(100, 300);
            $size = $this->faker->randomElement([21, 25, 27, 25, 27, 27]);
            $total_qty = $qty * $size;
            $total_commission = $total_qty * $commission;

            return [
                'unit' => PackingType::Thaan,
                'total_qty' => $total_qty,
                'qty' => $qty,
                'size' => $size,
                'price' => $price,
                'commission' => $commission,
                'total_commission' => $total_commission,
                'total_amount' => $price * $total_qty,
            ];
        });
    }
}
