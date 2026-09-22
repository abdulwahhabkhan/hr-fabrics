<?php

namespace Database\Factories\Sales;

use App\Enums\PackingType;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReturnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SalesReturn::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sr' => $this->faker->randomNumber(),
            'invoice_no' => $this->faker->bothify('SOR-######'),
            'order_no' => $this->faker->word(),
            'payment_mode' => $this->faker->word(),
            'info' => $this->faker->word(),
            'total_qty' => 0,
            'amount' => 0,
            'discount' => rand(1, 30),
            'expenses' => rand(1, 10),
            'total_amount' => 0,
            'status' => ReturnStatus::Open,
            'created_by' => fn () => User::factory(),
            'customer_id' => fn () => Account::factory(),
        ];
    }

    public function items($count): self
    {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $product = Product::factory()->create();
            $item = [];
            $qty = rand(2, 10);
            $rate = rand(100, 500);
            $item['finish'] = $product->finish;
            $item['name'] = $product->name;
            $item['size'] = $product->size;
            $item['qty'] = $qty;
            $item['rate'] = $rate;
            $item['product'] = $product;
            $item['unit'] = PackingType::Thaan->name;
            $item['total_qty'] = $product->size * $qty;
            $items[] = $item;
        }

        return $this->state([
            'items' => $items,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReturnStatus::Closed,
            'transaction_date' => today(),
        ]);
    }
}
