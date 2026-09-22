<?php

namespace Database\Factories\Accounts;

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Accounts\JournalDetail>
 */
class JournalDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cr = $this->faker->boolean();
        $dr = $this->faker->randomNumber(3);
        if ($cr) {
            $cr = $dr;
            $dr = 0;
        }

        return [
            'journal_id' => fn () => Journal::factory(),
            'account_id' => fn () => Account::factory(),
            'dr' => $dr,
            'cr' => $cr,
        ];
    }

    public function debit(int $val): static
    {
        return $this->state(fn (array $attributes) => [
            'dr' => $val,
            'cr' => 0,
        ]);
    }

    public function credit(int $val): static
    {
        return $this->state(fn (array $attributes) => [
            'dr' => 0,
            'cr' => $val,
        ]);
    }
}
