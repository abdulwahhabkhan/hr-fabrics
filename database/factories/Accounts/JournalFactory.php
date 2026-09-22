<?php

namespace Database\Factories\Accounts;

use App\Enums\JournalHead;
use App\Models\Accounts\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'head' => fake()->randomElement(JournalHead::cases()),
            'reference_no' => 'REF-'.fake()->randomNumber(4),
            'detail' => fake()->sentence(),
            'posted_at' => fake()->dateTimeBetween('-2 months'),
            'user_id' => 1,
        ];
    }
}
