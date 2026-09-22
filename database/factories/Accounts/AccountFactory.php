<?php

namespace Database\Factories\Accounts;

use App\Enums\AccountType;
use App\Enums\DiscountType;
use App\Models\Accounts\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(AccountType::cases()),
            'name' => fake()->name(),
            'name_urdu' => fake('ar_EG')->name,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => [
                'address' => fake()->streetName(),
                'city' => fake()->city(),
                'region' => 'Region',
                'country' => 'Pakistan',
            ],
            'created_by' => fn () => User::factory(),
        ];
    }

    public function supplier(): self
    {
        return $this->state(function () {
            return [
                'type' => AccountType::Supplier,
            ];
        });
    }

    public function partner(): self
    {
        return $this->state(function () {
            return [
                'type' => AccountType::Partner,
            ];
        });
    }

    public function customer(int $discount = 0, ?DiscountType $discountType = null): self
    {
        return $this->state(function () use ($discount, $discountType) {
            return [
                'type' => AccountType::Customer,
                'credit' => fake()->boolean(),
                'discount' => $discount,
                'discount_type' => $discountType ?? DiscountType::FixedPerMeter,
                'limit' => fake()->randomNumber(3),
            ];
        });
    }

    public function agent(): self
    {
        return $this->state(function () {
            return [
                'type' => AccountType::Agent,
            ];
        });
    }

    public function expense(): self
    {
        return $this->state(function () {
            return [
                'type' => AccountType::Expenses,
            ];
        });
    }

    public function cash(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::CashAccount->value,
            'type' => AccountType::Assets->value,
        ]);
    }

    public function bank(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Bank->value,
        ]);
    }

    public function advances(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Advances,
        ]);
    }

    public function drawings(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Drawings,
        ]);
    }

    public function otherReceivable(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::OtherReceivable,
        ]);
    }

    public function payable(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Payable,
        ]);
    }

    public function charity(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Charity,
        ]);
    }

    public function storeType(): self
    {
        return $this->state(function () {
            return [
                'type' => AccountType::Store,
            ];
        });
    }
}
