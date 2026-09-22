<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\UnLockOrder;
use App\Enums\OrderStatus;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->creatCashAccount();
});

test('unlock reopens a closed order, wiping its journal and allocated inventory', function () {
    // Arrange
    /** @var Order $order */
    $order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan(), 'items')
        ->create();
    $item = $order->items->first();
    $this->addInventory($item->product, $item->unit, $item->qty, $item->size);
    resolve(ConfirmOrder::class)->handle($order, $this->user);

    assertDatabaseCount(Journal::class, 1);
    assertDatabaseCount(Inventory::class, 1);

    // Action
    resolve(UnLockOrder::class)->handle($order, $this->user);

    // Assert
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Open);
    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $order->getMorphClass(),
        'loggable_id' => $order->id,
        'log' => json_encode([
            'action' => 'Sale Order UnLocked', 'user' => ['id' => $this->user->id, 'name' => $this->user->name],
        ]),
    ]);
});
