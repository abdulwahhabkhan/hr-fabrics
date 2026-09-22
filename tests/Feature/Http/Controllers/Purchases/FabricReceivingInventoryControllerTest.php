<?php

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

test('stock inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.inventory');
    $stock = FabricReceiving::factory()->create(['status' => StatusText::Close]);
    Inventory::factory()->create([
        'stockable_id' => $stock->id,
        'stockable_item_id' => $stock->id,
        'stockable_type' => $stock->getMorphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.inventory', $stock));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories', 1)
        ->has('back_url')
        ->has('page_header')
    );
});

test('stock inventory detail is forbidden for an open stock', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.inventory');
    $stock = FabricReceiving::factory()->create(['status' => StatusText::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.inventory', $stock));

    // Assert
    $response->assertForbidden();
});
