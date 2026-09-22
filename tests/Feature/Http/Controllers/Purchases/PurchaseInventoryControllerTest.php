<?php

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

test('receipt inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.inventory');
    $stock = FabricReceiving::factory()->create();
    $receipt = Purchase::factory()->create(['status' => StatusText::Close, 'stock_ids' => (string) $stock->id]);
    Inventory::factory()->create([
        'stockable_id' => $stock->id,
        'stockable_item_id' => $stock->id,
        'stockable_type' => FabricReceiving::morphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.inventory', $receipt));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories')
        ->has('back_url')
        ->has('page_header')
    );
});

test('receipt inventory detail is forbidden for an open receipt', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.inventory');
    $receipt = Purchase::factory()->create(['status' => StatusText::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.inventory', $receipt));

    // Assert
    $response->assertForbidden();
});
