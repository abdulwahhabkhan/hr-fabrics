<?php

namespace Database\Factories\Purchase;

use App\Models\Purchase\Bilti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bilti>
 */
class BiltiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Bilti>
     */
    protected $model = Bilti::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
