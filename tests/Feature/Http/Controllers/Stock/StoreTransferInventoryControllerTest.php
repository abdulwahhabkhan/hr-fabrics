<?php

use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;
use Inertia\Testing\AssertableInertia as Assert;

test('store transfer inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.store-transfers.inventory');
    $transfer = StoreTransfer::factory()->closed()->create();
    Inventory::factory()->withPurchase()->create([
        'outbound_id' => $transfer->id,
        'outbound_type' => $transfer->getMorphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.inventory', $transfer));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories', 1)
        ->where('inventories.0.reference_no', $transfer->transfer_no)
        ->has('back_url')
        ->has('page_header')
    );
});

test('store transfer inventory detail is forbidden for an open transfer', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.store-transfers.inventory');
    $transfer = StoreTransfer::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.inventory', $transfer));

    // Assert
    $response->assertForbidden();
});

test('store transfer inventory detail is forbidden without the inventory permission', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $transfer = StoreTransfer::factory()->closed()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.inventory', $transfer));

    // Assert
    $response->assertForbidden();
});
