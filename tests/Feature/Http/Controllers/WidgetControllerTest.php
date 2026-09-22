<?php

use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

it('shows avg sale meter widget without filters', function (): void {
    $this->actingAs($this->getAdmin());

    $order = Order::factory()->closed()->create();
    OrderItem::factory()->for($order)->create([
        'unit' => PackingType::Thaan,
        'qty' => 2,
        'total_qty' => 10,
        'total_amount' => 1000,
    ]);

    $response = $this->get(route('widgets.average-sale-meter'));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Widget/AvgSaleWidget')
                ->has('filters')
                ->has('sales', 1)
                ->where('sales.0.unit', PackingType::Thaan->value)
                ->where('sales.0.qty', 10)
                ->where('sales.0.avg_meter', 100)
                ->where('sales.0.per_trans', 1000);
        });
});

it('returns avg sale meter widget filtered by date range', function (): void {
    $this->actingAs($this->getAdmin());

    $inRange = Order::factory()->create([
        'status' => OrderStatus::Close,
        'transaction_date' => now()->subDays(2),
    ]);
    OrderItem::factory()->for($inRange)->create([
        'unit' => PackingType::Suit,
        'qty' => 1,
        'total_qty' => 6,
        'total_amount' => 600,
    ]);

    $outOfRange = Order::factory()->create([
        'status' => OrderStatus::Close,
        'transaction_date' => now()->subDays(30),
    ]);
    OrderItem::factory()->for($outOfRange)->create([
        'unit' => PackingType::Suit,
        'qty' => 5,
        'total_qty' => 30,
        'total_amount' => 3000,
    ]);

    $params = [
        'start_date' => now()->subDays(7)->toDateString(),
        'end_date' => now()->toDateString(),
    ];

    $response = $this->get(route('widgets.average-sale-meter', $params));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Widget/AvgSaleWidget')
                ->has('filters')
                ->has('sales', 1)
                ->where('sales.0.unit', PackingType::Suit->value)
                ->where('sales.0.qty', 6)
                ->where('sales.0.amount', '600.00');
        });
});

it('excludes orders that are not confirmed', function (): void {
    $this->actingAs($this->getAdmin());

    $order = Order::factory()->create([
        'status' => OrderStatus::Open,
        'transaction_date' => now(),
    ]);
    OrderItem::factory()->for($order)->create();

    $response = $this->get(route('widgets.average-sale-meter'));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Widget/AvgSaleWidget')
                ->has('sales', 0);
        });
});
