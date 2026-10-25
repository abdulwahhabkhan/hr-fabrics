<?php

use App\Enums\StockLocation;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Inertia\Testing\AssertableInertia as Assert;

test('stock transfer inventory detail lists stock moved out of the source and into the destination', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.stock-transfers.inventory');
    $transfer = StockTransfer::factory()->confirmed()->create();
    $bookedOut = Inventory::factory()->withPurchase()->create([
        'outbound_id' => $transfer->id,
        'outbound_type' => $transfer->getMorphClass(),
    ]);
    $arrived = Inventory::factory()->withPurchase()->create([
        'parent_id' => $bookedOut->id,
        'location' => StockLocation::WarehouseFSD,
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('stocks.stock-transfers.inventory', $transfer));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories', 2)
        ->where('inventories.0.id', $bookedOut->id)
        ->where('inventories.0.direction', 'out')
        ->where('inventories.1.id', $arrived->id)
        ->where('inventories.1.direction', 'in')
        ->where('inventories.1.reference_no', $transfer->transfer_no)
        ->has('sections', 2)
        ->where('hide_thaan_qty', true)
        ->where('parent.reference_no', $transfer->transfer_no)
    );
});

test('stock transfer inventory detail leaves the qty out of a thaan transfer summary', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.stock-transfers.inventory');
    $transfer = StockTransfer::factory()->thaan()->confirmed()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.stock-transfers.inventory', $transfer));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->where('parent.details', fn ($details) => collect($details)->pluck('label')->doesntContain('Qty'))
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
