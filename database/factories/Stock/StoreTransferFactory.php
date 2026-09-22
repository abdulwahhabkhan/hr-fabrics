<?php

namespace Database\Factories\Stock;

use App\Enums\StoreTransferStatus;
use App\Enums\StoreTransferType;
use App\Models\Accounts\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Stock\StoreTransfer>
 */
class StoreTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory()->storeType(),
            'created_by' => fn () => User::factory(),
            'transfer_sr' => fake()->unique()->randomNumber(5),
            'transfer_no' => '2609'.fake()->unique()->randomNumber(5),
            'total' => 0,
            'total_qty' => 0,
            'status' => StoreTransferStatus::Open,
        ];
    }

    public function closed(): self
    {
        return $this->state([
            'status' => StoreTransferStatus::Closed,
            'confirmed_at' => today(),
        ]);
    }

    public function storeType(): self
    {
        return $this->state([
            'type' => StoreTransferType::Store->value,
        ]);
    }

    public function returnType(): self
    {
        return $this->state([
            'type' => StoreTransferType::Return->value,
        ]);
    }
}
