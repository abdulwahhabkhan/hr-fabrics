<?php

use App\Enums\ReturnStatus;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

test('po return inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.inventory');
    $return = PurchaseReturn::factory()->closed()->create();
    Inventory::factory()->withPurchase()->create([
        'outbound_id' => $return->id,
        'outbound_type' => $return->getMorphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.inventory', $return));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories')
        ->has('back_url')
        ->has('page_header')
    );
});

test('po return inventory detail is forbidden for an open return', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.inventory');
    $return = PurchaseReturn::factory()->create(['status' => ReturnStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.inventory', $return));

    // Assert
    $response->assertForbidden();
});
