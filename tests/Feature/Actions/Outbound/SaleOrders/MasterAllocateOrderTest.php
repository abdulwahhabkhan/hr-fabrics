<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleOrders\MasterAllocateOrder;
use App\Enums\OrderAllocation;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = $this->getAdmin();
});

/**
 * A confirmed order needing 20 meters of one thaan product.
 */
function orderNeedingTwentyMeters(Product $product): Order
{
    $order = Order::factory()->closed()->create(['transaction_date' => '2026-10-05']);
    OrderItem::factory()->thaan()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'qty' => 1,
        'size' => 20,
        'total_qty' => 20,
    ]);

    return $order;
}

test('allocate books stock for an unallocated order and marks it allocated', function () {
    $product = Product::factory()->create();
    $stock = Inventory::factory()->thaan()->withPurchase()->create(['product_id' => $product->id, 'qty' => 2, 'meters' => 50]);
    $order = orderNeedingTwentyMeters($product);

    resolve(MasterAllocateOrder::class)->handle($order, $this->user);

    expect($order->refresh()->allocation)->toBe(OrderAllocation::Allocated)
        ->and((float) $order->inventories()->sum('meters'))->toBe(20.0)
        ->and($stock->refresh()->meters)->toEqual(30);
});

test('allocate rebooks a partially allocated order without booking its meters twice', function () {
    $product = Product::factory()->create();
    Inventory::factory()->thaan()->withPurchase()->create(['product_id' => $product->id, 'qty' => 2, 'meters' => 50]);
    $order = orderNeedingTwentyMeters($product);
    Inventory::factory()->thaan()->withPurchase()->create([
        'product_id' => $product->id,
        'qty' => 1,
        'meters' => 8,
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $order->id,
        'outbound_item_id' => $order->items()->value('id'),
    ]);
    $order->update(['allocation' => OrderAllocation::PartiallyAllocated]);

    resolve(MasterAllocateOrder::class)->handle($order, $this->user);

    expect($order->refresh()->allocation)->toBe(OrderAllocation::Allocated)
        ->and((float) $order->inventories()->sum('meters'))->toBe(20.0);
});

test('allocate fails without changes when stock does not cover the order', function () {
    $product = Product::factory()->create();
    Inventory::factory()->thaan()->withPurchase()->create(['product_id' => $product->id, 'qty' => 1, 'meters' => 15]);
    $order = orderNeedingTwentyMeters($product);

    expect(fn () => resolve(MasterAllocateOrder::class)->handle($order, $this->user))
        ->toThrow(ValidationException::class);

    expect($order->refresh()->allocation)->toBe(OrderAllocation::Unallocated)
        ->and($order->inventories()->exists())->toBeFalse()
        ->and($order->logs()->exists())->toBeFalse();
});
