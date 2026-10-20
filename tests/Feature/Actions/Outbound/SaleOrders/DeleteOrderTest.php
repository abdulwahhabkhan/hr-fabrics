<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\DeleteOrder;
use App\Enums\OrderStatus;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->creatCashAccount();
});

test('delete removes an open order with its items, journal entries and releases allocated inventory', function () {
    // Arrange
    /** @var Order $order */
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan(), 'items')
        ->create();
    $item = $order->items->first();
    $this->addInventory($item->product, $item->unit, $item->qty, $item->size);
    resolve(ConfirmOrder::class)->handle($order, $this->user);
    $order->update(['status' => OrderStatus::Open]);

    assertDatabaseCount(Journal::class, 1);
    assertDatabaseHas(Inventory::class, ['outbound_id' => $order->id]);

    // Action
    resolve(DeleteOrder::class)->handle($order, $this->user);

    // Assert
    assertSoftDeleted($order);
    assertDatabaseCount(OrderItem::class, 0);
    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
    assertDatabaseCount(Inventory::class, 1);
    assertDatabaseHas(Inventory::class, [
        'outbound_type' => null,
        'outbound_id' => null,
        'outbound_item_id' => null,
        'meters' => $item->total_qty,
    ]);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $order->getMorphClass(),
        'loggable_id' => $order->id,
        'log->action' => 'Sale Order Deleted',
        'log->user->id' => $this->user->id,
    ]);
});

test('delete refuses a closed order', function () {
    // Arrange
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan(), 'items')
        ->create();

    // Action
    resolve(DeleteOrder::class)->handle($order, $this->user);
})->throws(ActionNotAllowedException::class);
