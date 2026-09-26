<?php

namespace Database\Seeders;

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Enums\DiscountType;
use App\Enums\OrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentMode;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use App\Models\User;
use App\Services\OrderService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

/**
 * Seeds confirmed sale orders (cash and credit, some through the agent) and sales
 * returns dated across last month and today, so outbound reports have data.
 *
 * Sells stock booked by InboundSeeder and reuses the accounts created by BaseSeeder.
 */
class OutboundSeeder extends Seeder
{
    use SeedsTransactions;

    private const int ORDERS_LAST_MONTH = 20;

    private const int ORDERS_TODAY = 4;

    private const int RETURNS_LAST_MONTH = 4;

    private const int RETURNS_TODAY = 1;

    private const float AGENT_COMMISSION_PER_METER = 1;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $this->assignAgentExpenseAccount();
        $orders = $this->seedOrders();
        $this->seedSalesReturns($orders);
    }

    /**
     * Agent commission is booked against the agent's expense account.
     */
    private function assignAgentExpenseAccount(): void
    {
        $expenseAccount = Account::factory()
            ->recycle($this->getUsers())
            ->expense()
            ->create(['name' => 'Agent Commission']);

        $this->getAgent()?->update(['expense_account' => $expenseAccount->id]);
    }

    /**
     * Confirmed orders; confirming posts the sale ledger and issues inventory.
     *
     * @return Collection<int, Order>
     *
     * @throws Throwable
     */
    private function seedOrders(): Collection
    {
        return $this->transactionDates(self::ORDERS_LAST_MONTH, self::ORDERS_TODAY)
            ->map(function (CarbonImmutable $date): ?Order {
                return DB::transaction(function () use ($date): ?Order {
                    $inventories = $this->pickSellableInventories($date);
                    if ($inventories->isEmpty()) {
                        return null;
                    }

                    /** @var User $user */
                    $user = $this->getUsers()->random();
                    /** @var Account $customer */
                    $customer = $this->getCustomers()->random();
                    $agent = fake()->boolean() ? $this->getAgent() : null;
                    $isPaid = fake()->boolean(40);
                    $serial = (int) Order::query()->max('invoice_sr') + 1;

                    $order = Order::factory()->create([
                        'customer_id' => $customer->id,
                        'agent_id' => $agent->id ?? 0,
                        'created_by' => $user->id,
                        'invoice_sr' => $serial,
                        'invoice_no' => $date->format('ym').str($serial)->padLeft(3, '0'),
                        'discount_rate' => $customer->discount,
                        'discount_type' => $customer->discount_type,
                        'payment_mode' => $isPaid ? PaymentMode::Cash->value : PaymentMode::Credit->value,
                    ]);

                    foreach ($inventories as $inventory) {
                        $order->items()->create($this->orderItemAttributes($order, $inventory, $agent !== null));
                    }

                    $order->fill([
                        'paid' => $isPaid ? OrderPaid::Paid : OrderPaid::UnPaid,
                        'status' => OrderStatus::Close,
                        'transaction_date' => $date,
                    ])->save();

                    resolve(UpdateOrderTotal::class)->handle($order);
                    resolve(ConfirmOrder::class)->handle($order->refresh(), $user);

                    $this->stampTimestamps($order, $date);

                    return $order;
                });
            })
            ->filter()
            ->values();
    }

    /**
     * Confirmed returns of part of an earlier order's item.
     *
     * @param  Collection<int, Order>  $orders
     *
     * @throws Throwable
     */
    private function seedSalesReturns(Collection $orders): void
    {
        if ($orders->isEmpty()) {
            return;
        }

        $lastMonthReturns = self::RETURNS_LAST_MONTH;
        $total = $lastMonthReturns + self::RETURNS_TODAY;

        foreach (range(1, $total) as $number) {
            $isToday = $number > $lastMonthReturns;

            DB::transaction(function () use ($orders, $isToday): void {
                /** @var Order $order */
                $order = $orders->random();
                /** @var OrderItem $item */
                $item = $order->items()->get()->random();
                /** @var User $user */
                $user = $this->getUsers()->random();
                $date = $isToday
                    ? CarbonImmutable::today()
                    : $this->clampToToday(
                        CarbonImmutable::parse($order->transaction_date)->addDays(random_int(0, 3))
                    );
                $serial = (int) SalesReturn::query()->max('sr') + 1;
                $itemAttributes = $this->returnItemAttributes($item);

                $return = SalesReturn::factory()->create([
                    'sr' => $serial,
                    'invoice_no' => 'SOR-'.$date->format('ym').str($serial)->padLeft(3, '0'),
                    'order_no' => $order->invoice_no,
                    'customer_id' => $order->customer_id,
                    'agent_id' => $order->agent_id ?: null,
                    'created_by' => $user->id,
                    'payment_mode' => fake()->randomElement(PaymentMode::cases())->value,
                    'info' => [],
                    'total_qty' => $itemAttributes['total_qty'],
                    'amount' => $itemAttributes['total_amount'],
                    'discount' => 0,
                    'expenses' => 0,
                    'total_amount' => $itemAttributes['total_amount'],
                    'commission' => $itemAttributes['total_commission'],
                    'status' => ReturnStatus::Closed,
                    'transaction_date' => $date,
                ]);

                $return->returnItems()->create($itemAttributes);

                resolve(ConfirmSaleReturn::class)->handle($return->refresh(), $user);

                $this->stampTimestamps($return, $date);
            });
        }
    }

    /**
     * Distinct stock lines received on or before the order date.
     *
     * @return Collection<int, Inventory>
     *
     * @throws RandomException
     */
    private function pickSellableInventories(CarbonImmutable $date): Collection
    {
        return Inventory::query()
            ->available()
            ->whereIn('unit', [PackingType::Box, PackingType::Thaan])
            ->whereDate('transaction_date', '<=', $date)
            ->inRandomOrder()
            ->limit(random_int(1, 3))
            ->get();
    }

    /**
     * Sells part of the stock line at a markup, priced the way AddOrderItemController does.
     *
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    private function orderItemAttributes(Order $order, Inventory $inventory, bool $hasAgent): array
    {
        $markup = fake()->randomFloat(2, 1.15, 1.4);

        if ($inventory->unit === PackingType::Box) {
            $qty = random_int(1, max(1, intdiv($inventory->qty, 2)));
            $size = $inventory->size;
            $totalQty = $qty * $size;
            $price = round(($inventory->cost ?: random_int(2000, 5000)) * $markup);
            $totalAmount = $qty * $price;
        } else {
            $qty = 1;
            $totalQty = max(1, (int) floor($inventory->meters * fake()->randomFloat(2, 0.2, 0.5)));
            $size = $totalQty;
            $price = round(($inventory->cost ?: random_int(100, 500)) * $markup);
            $totalAmount = $totalQty * $price;
        }

        $discount = $order->discount_type === DiscountType::PercentageOnTotal
            ? $totalAmount * ($order->discount_rate / 100)
            : $totalQty * $order->discount_rate;
        $commission = $hasAgent ? self::AGENT_COMMISSION_PER_METER : 0;

        return [
            'product_id' => $inventory->product_id,
            'unit' => $inventory->unit->value,
            'size' => $size,
            'qty' => $qty,
            'price' => $price,
            'total_qty' => $totalQty,
            'total_amount' => $totalAmount,
            'discount' => $discount,
            'commission' => $commission,
            'total_commission' => resolve(OrderService::class)->getAgentCommission([
                'commission' => $commission,
                'total_qty' => $totalQty,
                'total_amount' => $totalAmount,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    private function returnItemAttributes(OrderItem $item): array
    {
        if ($item->unit === PackingType::Box) {
            $qty = random_int(1, max(1, intdiv($item->qty, 2)));
            $totalQty = $qty * $item->size;
            $totalAmount = $qty * $item->price;
            $size = $item->size;
        } else {
            $qty = 1;
            $totalQty = max(1, (int) floor($item->total_qty / 2));
            $totalAmount = $totalQty * $item->price;
            $size = $totalQty;
        }

        return [
            'product_id' => $item->product_id,
            'unit' => $item->unit->value,
            'size' => $size,
            'qty' => $qty,
            'total_qty' => $totalQty,
            'rate' => $item->price,
            'commission' => (string) $item->commission,
            'total_commission' => (float) $item->commission * $totalQty,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * @return Collection<int, Account>
     */
    private function getCustomers(): Collection
    {
        return once(fn () => Account::query()->customers()->get());
    }

    private function getAgent(): ?Account
    {
        return once(fn () => Account::query()->agents()->first());
    }
}
