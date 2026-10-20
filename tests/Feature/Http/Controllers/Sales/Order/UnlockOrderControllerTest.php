<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('unlock forbidden', function (OrderStatus $status, bool $travel) {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->create(['status' => $status]);
    if ($travel) {
        $this->travel(2)->days();
    }

    // Act
    $response = $this->actingAs($user)
        ->post(route('actions.order.open', $order->id), []);

    // Assert
    $response->assertForbidden();
})->with([
    'open order' => [OrderStatus::Open, false],
    'closed but not today' => [OrderStatus::Close, true],
]);

test('unlock reopens closed order', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->creatCashAccount();
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan(), 'items')
        ->create();
    $item = $order->items->first();
    $this->addInventory($item->product, $item->unit, $item->qty, $item->size);
    resolve(ConfirmOrder::class)->handle($order, $user);

    // Act
    $response = $this->actingAs($user)
        ->post(route('actions.order.open', $order->id), []);

    // Assert
    $response->assertRedirect(route('sales.orders.edit', $order->id));
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Open);
    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
    assertDatabaseCount(Inventory::class, 1);
    assertDatabaseHas(Inventory::class, ['outbound_id' => null, 'meters' => $item->total_qty]);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $order->getMorphClass(),
        'loggable_id' => $order->id,
        'log->action' => 'Sale Order UnLocked',
        'log->user->id' => $user->id,
        'log->user->name' => $user->name,
    ]);
});

test('unlock releases partially issued stock as a separate available line', function (PackingType $unit) {
    // Arrange
    $user = $this->getAdmin();
    $this->creatCashAccount();
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->state(['unit' => $unit, 'qty' => 12, 'size' => 16, 'total_qty' => 192]), 'items')
        ->create();
    $this->addInventory($order->items->first()->product, $unit, 24, 16);
    resolve(ConfirmOrder::class)->handle($order, $user);

    // Act
    $response = $this->actingAs($user)
        ->post(route('actions.order.open', $order->id), []);

    // Assert
    $response->assertRedirect(route('sales.orders.edit', $order->id));
    assertDatabaseCount(Inventory::class, 2);
    expect(Inventory::query()->available()->sum('meters'))->toEqual(384)
        ->and(Inventory::query()->booked()->count())->toBe(0);
})->with([
    'thaan' => [PackingType::Thaan],
    'box' => [PackingType::Box],
]);

test('re-confirming an unlocked order issues its stock only once', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->creatCashAccount();
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan()->state(['qty' => 12, 'size' => 16, 'total_qty' => 192]), 'items')
        ->create();
    $this->addInventory($order->items->first()->product, PackingType::Thaan, 24, 16);
    resolve(ConfirmOrder::class)->handle($order, $user);
    $this->actingAs($user)->post(route('actions.order.open', $order->id), []);
    $order->refresh()->update(['status' => OrderStatus::Close]);

    // Act
    resolve(ConfirmOrder::class)->handle($order, $user);

    // Assert
    expect(Inventory::query()->available()->sum('meters'))->toEqual(192)
        ->and(Inventory::query()->booked()->sum('meters'))->toEqual(192);
});
