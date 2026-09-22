<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Enums\OrderStatus;
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
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $order->getMorphClass(),
        'loggable_id' => $order->id,
        'log' => json_encode([
            'action' => 'Sale Order UnLocked', 'user' => ['id' => $user->id, 'name' => $user->name],
        ]),
    ]);
});
