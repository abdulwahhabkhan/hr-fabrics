<?php

namespace Database\Factories\Catalog;

use App\Models\Catalog\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->paragraph(1),
            'created_by' => fn () => User::factory(),
        ];
    }
}
