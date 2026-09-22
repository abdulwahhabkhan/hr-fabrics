<?php

namespace Database\Seeders;

use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::factory()
            ->state(new Sequence(function ($seq) {
                return ['invoice_no' => '2107'.$seq->index];
            }))
            ->count(10)
            ->hasItems(rand(1, 4), [
                'product_id' => function () {
                    return Product::query()->inRandomOrder()->first()->id;
                },
            ])
            ->create();

        $customers = Account::query()
            ->selectForSales()
            ->customers()
            ->orderByName()
            ->get();
        foreach ($customers as $customer) {
            Order::factory()
                ->state(new Sequence(function ($seq) {
                    return ['invoice_no' => '2106'.$seq->index];
                }))
                ->count(1)
                ->hasItems(rand(1, 4), [
                    'product_id' => function () {
                        return Product::query()->inRandomOrder()->first()->id;
                    },
                ])
                ->create([
                    'customer_id' => $customer->customer_id,
                    // 'discount_rate' => $customer->discount,
                    // 'affiliate_rate' => $customer->commission_rate ?? 1,
                ]);
        }

        $orders = Order::all();
        foreach ($orders as $order) {
            resolve(UpdateOrderTotal::class)->handle($order);
        }
    }
}
