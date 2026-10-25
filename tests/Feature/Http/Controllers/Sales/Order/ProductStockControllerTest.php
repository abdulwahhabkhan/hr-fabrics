<?php

use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;

test('returns available stock grouped by unit and size', function () {
    $product = Product::factory()->thaan()->create();
    $this->addInventory($product, PackingType::Suit, 2, 4.5, 100);
    $this->addInventory($product, PackingType::Suit, 3, 4.5, 100);
    $this->addInventory($product, PackingType::Thaan, 1, 20, 100);

    $response = $this->actingAs($this->getAdmin())
        ->getJson(route('ajax.so.product.stock', $product));

    $response->assertOk()
        ->assertJsonCount(2, 'stock')
        ->assertJsonFragment(['unit' => 'Suit', 'size' => 4.5, 'qty' => 5, 'meters' => 22.5])
        ->assertJsonFragment(['unit' => 'Thaan', 'size' => 0, 'qty' => 1, 'meters' => 20]);
});

test('excludes stock without cost', function () {
    $product = Product::factory()->thaan()->create();
    $this->addInventory($product, PackingType::Suit, 2, 4.5, 100);
    $this->addInventory($product, PackingType::Suit, 3, 4.5);

    $this->actingAs($this->getAdmin())
        ->getJson(route('ajax.so.product.stock', $product))
        ->assertOk()
        ->assertJsonCount(1, 'stock')
        ->assertJsonFragment(['unit' => 'Suit', 'size' => 4.5, 'qty' => 2, 'meters' => 9]);
});

test('excludes stock of other products', function () {
    $product = Product::factory()->thaan()->create();
    $this->addInventory(Product::factory()->thaan()->create(), PackingType::Thaan, 1, 20, 100);

    $this->actingAs($this->getAdmin())
        ->getJson(route('ajax.so.product.stock', $product))
        ->assertOk()
        ->assertJsonCount(0, 'stock');
});

test('guests cannot view stock', function () {
    $product = Product::factory()->thaan()->create();

    $this->getJson(route('ajax.so.product.stock', $product))->assertUnauthorized();
});

test('returns stock separately for each location', function () {
    $product = Product::factory()->thaan()->create();
    $this->addInventory($product, PackingType::Suit, 2, 4.5, 100);
    $this->addInventory($product, PackingType::Suit, 3, 4.5, 100);
    Inventory::query()->latest('id')->first()->update(['location' => StockLocation::WarehouseFSD]);

    $this->actingAs($this->getAdmin())
        ->getJson(route('ajax.so.product.stock', $product))
        ->assertOk()
        ->assertJsonCount(2, 'stock')
        ->assertJsonFragment(['location' => 'shop', 'location_label' => 'Shop', 'unit' => 'Suit', 'qty' => 2])
        ->assertJsonFragment(['location' => 'warehouse_fsd', 'location_label' => 'Warehouse FSD', 'unit' => 'Suit', 'qty' => 3]);
});
