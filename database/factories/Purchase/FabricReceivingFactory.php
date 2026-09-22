<?php

namespace Database\Factories\Purchase;

use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

final class FabricReceivingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FabricReceiving::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'supplier_id' => function () {
                return Account::factory()->supplier();
            },
            'created_by' => function () {
                return User::factory();
            },
            'invoice_no' => date('Ym').fake()->randomNumber(3),
            'bilti_no' => 'BT-'.fake()->randomNumber(),
            'lot_no' => fake()->bothify('LOT-####'),
            'total_qty' => 0,
            'status' => StatusText::Open,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (FabricReceiving $order) {
            // Log::info("Order making");
        })->afterCreating(function (FabricReceiving $order) {
            $total = FabricReceivingItem::where('fabric_receiving_id', $order->id)->sum(DB::raw('total_qty'));
            $order->total_qty = $total;
            $order->save();
        });
    }

    public function confirmed(): self
    {
        return $this->state(function () {
            return [
                'status' => StatusText::Close,
                'transaction_date' => fake()->dateTimeBetween('-30 days'),
            ];
        });
    }

    public function cancel(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusText::Cancel,
        ]);
    }
}
