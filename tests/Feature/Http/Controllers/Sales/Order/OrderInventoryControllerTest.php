<?php

use App\Enums\OrderStatus;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

test('order inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.orders.inventory');
    $order = Order::factory()->closed()->create();
    Inventory::factory()->withPurchase()->create([
        'outbound_id' => $order->id,
        'outbound_type' => $order->getMorphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.orders.inventory', $order));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories')
        ->has('back_url')
        ->has('page_header')
    );
});

test('order inventory detail is forbidden for an open order', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.orders.inventory');
    $order = Order::factory()->create(['status' => OrderStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.orders.inventory', $order));

    // Assert
    $response->assertForbidden();
});
