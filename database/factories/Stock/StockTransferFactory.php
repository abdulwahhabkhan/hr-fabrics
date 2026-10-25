<?php

namespace Database\Factories\Stock;

use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 5);
        $size = fake()->randomElement([5, 5.5, 6]);

        return [
            'transfer_no' => 'LTR-'.fake()->unique()->randomNumber(6),
            'product_id' => Product::factory(),
            'unit' => PackingType::Box,
            'size' => $size,
            'qty' => $qty,
            'meters' => $qty * $size,
            'from_location' => StockLocation::Shop,
            'to_location' => StockLocation::WarehouseFSD,
            'status' => StatusText::Open,
            'created_by' => User::factory(),
        ];
    }

    public function thaan(float $meters = 80, int $qty = 2): self
    {
        return $this->state([
            'unit' => PackingType::Thaan,
            'size' => 0,
            'qty' => $qty,
            'meters' => $meters,
        ]);
    }

    public function fromWarehouse(): self
    {
        return $this->state([
            'from_location' => StockLocation::WarehouseFSD,
            'to_location' => StockLocation::Shop,
        ]);
    }

    public function confirmed(): self
    {
        return $this->state([
            'status' => StatusText::Close,
            'transaction_date' => today(),
            'confirmed_at' => now(),
        ]);
    }
}
