<?php

use App\Enums\StockLocation;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Inertia\Testing\AssertableInertia as Assert;

test('stock transfer inventory detail lists the lots opened at the destination', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.stock-transfers.inventory');
    $transfer = StockTransfer::factory()->confirmed()->create();
    $bookedOut = Inventory::factory()->withPurchase()->create([
        'outbound_id' => $transfer->id,
        'outbound_type' => $transfer->getMorphClass(),
    ]);
    Inventory::factory()->withPurchase()->create([
        'parent_id' => $bookedOut->id,
        'location' => StockLocation::WarehouseFSD,
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('stocks.stock-transfers.inventory', $transfer));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories', 1)
        ->where('inventories.0.parent_id', $bookedOut->id)
        ->where('inventories.0.reference_no', $transfer->transfer_no)
        ->where('parent.reference_no', $transfer->transfer_no)
    );
});

test('stock transfer inventory detail is forbidden for a draft transfer', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.stock-transfers.inventory');
    $transfer = StockTransfer::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.stock-transfers.inventory', $transfer));

    // Assert
    $response->assertForbidden();
});

test('stock transfer inventory detail is forbidden without the inventory permission', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $transfer = StockTransfer::factory()->confirmed()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.stock-transfers.inventory', $transfer));

    // Assert
    $response->assertForbidden();
});
