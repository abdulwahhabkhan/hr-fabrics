<?php

namespace Database\Factories\Purchase;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FabricReceivingItem>
 */
class FabricReceivingItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FabricReceivingItem>
     */
    protected $model = FabricReceivingItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $unit = $this->faker->randomElement(['Box', 'Thaan']);

        $qty = $this->faker->numberBetween(1, 10);

        if ($unit === 'Box') {
            $size = $this->faker->randomElement([5, 5.5, 6]);
            $total_qty = $qty;
        } else {
            $size = $this->faker->randomElement([18, 21, 27]);
            $total_qty = $qty * $size;
        }

        return [
            'fabric_receiving_id' => fn () => FabricReceiving::factory(),
            'product_id' => fn () => Product::factory(),
            'voucher_no' => 'VCR-'.$this->faker->randomNumber(3),
            'qty' => $qty,
            'size' => $size,
            'unit' => $unit,
            'total_qty' => $total_qty,
            'status' => 0,
        ];
    }

    public function box(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $size = $this->faker->randomElement([5, 5.5, 6, 6.7, 7.5, 7]);

            return [
                'unit' => PackingType::Box->value,
                'total_qty' => $qty * $size,
                'qty' => $qty,
                'size' => $size,
            ];
        });
    }

    public function suit(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(2);
            $size = $this->faker->randomElement([5, 5.5, 6, 6.7, 7.5, 7]);

            return [
                'unit' => PackingType::Suit->value,
                'total_qty' => $qty * $size,
                'qty' => $qty,
                'size' => $size,
            ];
        });
    }

    public function thaan(): self
    {
        return $this->state(function () {
            $qty = $this->faker->randomNumber(3);
            $size = $this->faker->randomElement([25, 21, 27, 27, 25, 21]);

            return [
                'unit' => PackingType::Thaan->value,
                'total_qty' => $qty * $size,
                'qty' => $qty,
                'size' => 0,
            ];
        });
    }
}
