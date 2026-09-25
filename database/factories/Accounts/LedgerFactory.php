<?php

namespace Database\Factories\Accounts;

use App\Models\Accounts\Ledger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ledger>
 */
class LedgerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Ledger>
     */
    protected $model = Ledger::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [

        ];
    }
}
