<?php

namespace Database\Factories\Stock;

use App\Models\Stock\ValueAddition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ValueAddition>
 */
class ValueAdditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = $this->faker->randomNumber(5);
        $packing_cost = round($cost * 10 / 100);
        $cut_piece = round($cost * 6 / 100);
        $total = $cost + $packing_cost - $cut_piece;

        return [
            'created_by' => User::factory(),
            'vendor_id' => User::factory(),
            'packed_by' => User::factory(),
            'lot_number' => $this->faker->bothify('########'),
            'material_detail' => 'Material Purchased',
            'packing_detail' => 'Packing Cost',
            'cost' => $cost,
            'packing_cost' => $packing_cost,
            'cut_piece_cost' => $cut_piece,
            'total_value' => $total,
        ];
    }
}
