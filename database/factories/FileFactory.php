<?php

namespace Database\Factories;

use App\Enums\FileType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'directory' => 'sales',
            'type' => FileType::PDF,
            'name' => $this->faker->word().'.pdf',
            'path' => 'files/'.$this->faker->uuid().'.pdf',
            'size' => $this->faker->numberBetween(1024, 5242880),
        ];
    }
}
