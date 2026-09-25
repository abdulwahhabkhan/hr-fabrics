<?php

namespace Database\Factories\Stock;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Inventory>
     */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::factory(),
            'unit' => fake()->randomElement(PackingType::cases()),
            'info' => [''],
            'size' => fake()->randomElement([21, 18, 27, 5, 6, 4.5]),
            'qty' => fake()->randomNumber(2),
            'meters' => fn ($a) => $a['qty'] * $a['size'],
        ];
    }

    public function box(): Factory
    {
        return $this->state(function () {
            $size = fake()->randomElement([5, 6, 5.5, 6.5, 6, 6]);
            $qty = rand(1, 9);

            return [
                'unit' => PackingType::Box,
                'size' => $size,
                'qty' => $qty,
                'meters' => $qty * $size,
            ];
        });
    }

    public function suit(): Factory
    {
        return $this->state(function () {
            $size = fake()->randomElement([5, 6, 5.5, 6.5, 6, 6]);
            $qty = rand(1, 9);

            return [
                'unit' => PackingType::Suit,
                'size' => $size,
                'qty' => $qty,
                'meters' => $qty * $size,
            ];
        });
    }

    public function thaan(): Factory
    {
        return $this->state(function () {
            $qty = rand(1, 9);
            $size = fake()->randomElement([28, 42, 36, 28, 36, 42, 42, 42]);

            return [
                'unit' => PackingType::Thaan,
                'size' => $size,
                'qty' => $qty,
                'meters' => $qty * $size,
            ];
        });
    }

    public function withPurchase(?FabricReceiving $stock = null): self
    {
        if (! $stock) {
            $stock = FabricReceiving::factory()->confirmed()->create();
        }

        return $this->state(function () use ($stock) {
            return [
                'stockable_item_id' => fake()->randomNumber(4),
                'stockable_type' => $stock->getMorphClass(),
                'stockable_id' => $stock->id,
                'transaction_date' => $stock->transaction_date,
            ];
        });
    }
}
