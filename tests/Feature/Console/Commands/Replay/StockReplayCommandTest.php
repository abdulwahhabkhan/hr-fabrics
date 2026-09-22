<?php

use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('replay stock rebuilds inventory for closed stock receiving', function () {
    // Arrange
    $stock = FabricReceiving::factory()
        ->confirmed()
        ->has(FabricReceivingItem::factory()->thaan()->count(2), 'items')
        ->create();
    $openStock = FabricReceiving::factory()
        ->has(FabricReceivingItem::factory()->thaan()->count(1), 'items')
        ->create();

    // Act
    $this->artisan('replay:stock')->assertSuccessful();

    // Assert
    assertDatabaseCount(Inventory::class, 2);
    foreach ($stock->items as $item) {
        assertDatabaseHas(Inventory::class, [
            'stockable_item_id' => $item->id,
            'product_id' => $item->product_id,
            'qty' => $item->qty,
        ]);
    }
    expect(Inventory::query()->where('stockable_item_id', $openStock->items->first()->id)->exists())->toBeFalse();
});
