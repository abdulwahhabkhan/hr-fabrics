<?php

namespace Database\Factories\Purchase;

use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class PurchaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Account::factory()->supplier(),
            'invoice_no' => '2107'.fake()->randomNumber(3),
            'bilti_no' => 'BL-'.fake()->randomNumber(3),
            'stock_id' => fake()->randomNumber(3),
            'bill_no' => 'B-'.fake()->randomNumber(3),
            'discount' => fake()->randomNumber(2),
            'total' => 0,
            'total_qty' => 0,
            'status' => StatusText::Open,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Purchase $receipt) {
            // Log::info("Order making");
        })->afterCreating(function (Purchase $receipt) {
            $total = PurchaseItem::query()
                ->where('purchase_id', $receipt->id)->sum(DB::raw('price*qty'));
            $receipt->total = $total;
            $receipt->save();
        });
    }

    public function confirmed(): self
    {
        return $this->state(function () {
            return [
                'status' => StatusText::Close,
                'transaction_date' => now(),
            ];
        });
    }
}
