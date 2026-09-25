<?php

namespace Database\Factories\Action;

use App\Models\Action\Log;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
final class LogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Log>
     */
    protected $model = Log::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'log' => [
                'action' => fake()->randomElement(['create', 'update', 'delete']),
            ],
        ];
    }
}
