<?php

use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;

test('fabric receiving delete single item', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan(),
        'items'
    )->create();

    $item = $stock->items()->first();

    // Act
    $response = $this->actingAs($user)
        ->delete(route('ajax.fabric-receiving.item.destroy', $item->id));

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(FabricReceivingItem::class, 0);
});
test('fabric receiving item delete', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan()->count(3),
        'items'
    )->create();

    $item = $stock->items()->first();

    // Act
    $response = $this->actingAs($user)
        ->delete(route('ajax.fabric-receiving.item.destroy', $item->id));

    // Assert
    $response->assertOk();
    $this->assertDatabaseMissing(FabricReceivingItem::class, $item->toArray());
    $this->assertDatabaseCount(FabricReceivingItem::class, 2);
});
