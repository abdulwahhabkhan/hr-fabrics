<?php

namespace Database\Factories\Purchase;

use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PurchaseReturn>
     */
    protected $model = PurchaseReturn::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $sr = fake()->randomNumber(3);

        return [
            'sr' => $sr,
            'invoice_no' => 'POR-'.$sr,
            'supplier_id' => fn () => Account::factory()->supplier(),
            'created_by' => fn () => User::factory(),
            'bilti_no' => 'BT'.fake()->randomNumber(3),
            'bill_no' => 'BL'.fake()->randomNumber(3),
            'info' => [],
            'total_qty' => 3,
            'total_amount' => 3000,
            'status' => ReturnStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Closed,
            'transaction_date' => fake()->dateTimeThisMonth(),
        ]);
    }
}
