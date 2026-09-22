<?php

namespace Database\Factories\Sales;

use App\Enums\DiscountType;
use App\Enums\OrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PurchaseType;
use App\Models\Accounts\Account;
use App\Models\Catalog\Brand;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => fn () => Account::factory()->customer(),
            'invoice_sr' => fake()->randomNumber(5),
            'invoice_no' => now()->format('ym').fake()->randomNumber(5),
            'created_by' => fn () => User::query()->first()->id,
            'payment_mode' => fake()->randomElement(['cash', 'credit']),
            'total' => 0,
            'discount' => 0,
            'discount_type' => DiscountType::FixedPerMeter,
            'discount_rate' => 0,
            'net_total' => 0,
            'paid' => 0,
            'purchase_type' => fake()->randomElement(PurchaseType::toValues()),
            'created_at' => fake()->dateTimeBetween('-100 days'),
            'status' => OrderStatus::Open,
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Order $order) {
            // Log::info("Order making");
        })->afterCreating(function (Order $order) {
            $total = OrderItem::where('order_id', $order->id)->sum(DB::raw('price*qty'));
            $order->total = $total;
            $order->net_total = $total;
            $order->save();
        });
    }

    public function paid(): self
    {
        $this->closed();

        return $this->state([
            'paid' => OrderPaid::Paid,
        ]);
    }

    public function fromShop(): self
    {
        return $this->state([
            'purchase_type' => PurchaseType::InPerson,
        ]);
    }

    public function closed(): self
    {
        return $this->state([
            'transaction_date' => today(),
            'status' => OrderStatus::Close,
        ]);
    }

    public function percentageDiscount(): self
    {
        return $this->state([
            'discount_type' => DiscountType::PercentageOnTotal,
        ]);
    }

    public function agent(): self
    {
        return $this->state([
            'agent_id' => fn () => Account::factory()->agent(),
        ]);
    }

    public function agent_per_meter(): self
    {
        $brand = Brand::factory()->create();

        return $this->state([
            'agent_id' => fn () => Account::factory()->agent(),
            'agent_rate' => ['brand_'.$brand->id => 1],
        ]);
    }

    public function agent_percentage(): self
    {
        $brand = Brand::factory()->create();

        return $this->state([
            'agent_id' => fn () => Account::factory()->agent(),
            'agent_rate' => ['brand_'.$brand->id => '1%'],
        ]);
    }
}
