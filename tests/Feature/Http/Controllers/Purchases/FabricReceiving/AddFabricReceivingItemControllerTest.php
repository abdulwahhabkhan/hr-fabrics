<?php

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;

test('fabric item add thaan', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan()->count(3),
        'items'
    )->create();
    $product = Product::factory()->box()->create()->first();
    $product['product_id'] = $product->id;
    $body = [
        'product' => $product,
        'qty' => 2,
        'unit' => 'Thaan',
        'voucher_no' => 'V100',
        'total_qty' => 100,
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.fabric-receiving.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(FabricReceivingItem::class, 4);
    unset($body['product']);
    $body['product_id'] = $product->id;
    $body['fabric_receiving_id'] = $stock->id;
    $this->assertDatabaseHas(FabricReceivingItem::class, $body);
});

test('fabric item update thaan', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan()->count(3),
        'items'
    )->create();
    $item = $stock->items()->first();
    $product = Product::factory()->box()->create()->first();
    $product['product_id'] = $product->id;
    $body = [
        'product' => $product,
        'item_id' => $item->id,
        'qty' => 2,
        'unit' => 'Thaan',
        'voucher_no' => 'V100',
        'total_qty' => 100,
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.fabric-receiving.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(FabricReceivingItem::class, 3);
    unset($body['product']);
    unset($body['item_id']);
    $body['product_id'] = $product->id;
    $body['id'] = $item->id;
    $this->assertDatabaseHas(FabricReceivingItem::class, $body);

});

test('fabric add item type ', function ($unit) {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan()->count(3),
        'items'
    )->create();
    $product = Product::factory()->box()->create()->first();
    $product['product_id'] = $product->id;
    $body = [
        'product' => $product,
        'qty' => 100,
        'unit' => $unit,
        'size' => 6,
        'voucher_no' => 'V100',
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.fabric-receiving.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(FabricReceivingItem::class, 4);
    unset($body['product']);
    $body['product_id'] = $product->id;
    $body['fabric_receiving_id'] = $stock->id;
    $body['total_qty'] = $body['qty'] * $body['size'];
    $this->assertDatabaseHas(FabricReceivingItem::class, $body);

})->with([
    'Box' => PackingType::Box->value,
    'Suit' => PackingType::Suit->value,
]);

test('fabric update item', function ($unit) {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->has(
        FabricReceivingItem::factory()->thaan()->count(3),
        'items'
    )->create();
    $item = $stock->items()->first();
    $product = Product::factory()->create()->first();
    $product['product_id'] = $product->id;
    $body = [
        'product' => $product,
        'item_id' => $item->id,
        'qty' => 2,
        'unit' => $unit,
        'size' => 5.5,
        'voucher_no' => 'V100',
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.fabric-receiving.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(FabricReceivingItem::class, 3);
    unset($body['product']);
    unset($body['item_id']);
    $body['product_id'] = $product->id;
    $body['id'] = $item->id;
    $body['total_qty'] = $body['qty'] * $body['size'];
    $this->assertDatabaseHas(FabricReceivingItem::class, $body);

})->with([
    'Box' => PackingType::Box->value,
    'Suit' => PackingType::Suit->value,
]);
