<?php

namespace Database\Factories\Catalog;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Finish;
use App\Models\Catalog\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $unit_price = fake()->randomNumber(2);
        $suit_price = $unit_price * 4.5;
        $isBox = fake()->boolean();
        $size = fake()->randomElement([27, 21, 25]);
        if ($isBox) {
            $unit_price = ($unit_price * 5) + 50;
            $suit_price = 0;
            $size = fake()->randomElement([5, 5.5, 6]);
        }

        return [
            'brand_id' => fn () => Brand::factory(),
            'name' => fake()->colorName(),
            'created_by' => fn () => User::factory(),
            'description' => fake()->realText(40),
            'finish' => function () {
                return Finish::factory()->count(1)->create()->first()->name;
            },
            'is_box' => $isBox,
            'size' => $size,
            'unit_price' => $unit_price,
            'suit_price' => $suit_price,
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            return [
                'is_box' => 1,
                'unit_price' => fake()->randomNumber(3),
                'size' => fake()->randomElement([5, 5.5, 6, 6.7, 7.5, 7]),
            ];
        });
    }

    public function suit(): self
    {
        return $this->state(function () {
            return [
                'is_box' => 0,
                'unit_price' => fake()->randomNumber(3),
                'suit_price' => fake()->randomNumber(2),
            ];
        });
    }

    public function thaan(): self
    {
        return $this->state(function () {
            return [
                'is_box' => 0,
                'unit_price' => fake()->randomNumber(3),
                'suit_price' => fake()->randomNumber(2),
            ];
        });
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }
}
