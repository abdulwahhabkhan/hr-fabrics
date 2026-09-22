<?php

namespace Database\Factories\Catalog;

use App\Models\Catalog\Finish;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinishFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Finish::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $color = $this->faker->unique()->colorName();

        return [
            'name' => $color,
            'description' => $color.' '.$this->faker->rgbcolor(),
        ];
    }
}
