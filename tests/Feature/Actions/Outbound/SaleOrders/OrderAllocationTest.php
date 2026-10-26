<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\SyncOrderAllocation;
use App\Actions\Outbound\SaleOrders\UnLockOrder;
use App\Enums\OrderAllocation;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->creatCashAccount();
    $this->order = Order::factory()
        ->closed()
        ->has(OrderItem::factory()->thaan(), 'items')
        ->create();
    $this->item = $this->order->items->first();
    $this->addInventory($this->item->product, $this->item->unit, $this->item->qty, $this->item->size);
});

test('orders start unallocated', function () {
    expect($this->order->refresh()->allocation)->toBe(OrderAllocation::Unallocated);
});

test('confirming an order books its stock and marks it allocated', function () {
    resolve(ConfirmOrder::class)->handle($this->order, $this->user);

    expect($this->order->refresh()->allocation)->toBe(OrderAllocation::Allocated);
});

test('unlocking an order releases its stock and marks it unallocated', function () {
    resolve(ConfirmOrder::class)->handle($this->order, $this->user);

    resolve(UnLockOrder::class)->handle($this->order, $this->user);

    expect($this->order->refresh()->allocation)->toBe(OrderAllocation::Unallocated);
});

test('an order with only part of its meters booked is partially allocated', function () {
    Inventory::query()->update([
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $this->order->id,
        'meters' => $this->item->total_qty / 2,
    ]);

    resolve(SyncOrderAllocation::class)->handle($this->order);

    expect($this->order->refresh()->allocation)->toBe(OrderAllocation::PartiallyAllocated);
});
