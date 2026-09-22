<?php

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('inventory view component loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    $item = FabricReceivingItem::factory()->create(['product_id' => $product->id]);
    $inventoryData = [
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'stockable_type' => FabricReceiving::morphClass(),
        'product_id' => $product->id,
        'transaction_date' => now()->subMonth(),
    ];
    Inventory::factory(2)->thaan()->create($inventoryData);
    Inventory::factory()->suit()->create($inventoryData);
    Inventory::factory()->box()->create($inventoryData);

    // Act
    $response = $this->actingAs($user)->get(route('stocks.inventories.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Stock/Inventory/InventoryIndex')
            ->has('items', 3)
    );
});

test('inventory view box filter by size', function () {
    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    $item = FabricReceivingItem::factory()->create(['product_id' => $product->id]);
    Inventory::factory(3)->box()
        ->create([
            'stockable_type' => FabricReceiving::morphClass(),
            'stockable_id' => $item->fabric_receiving_id,
            'stockable_item_id' => $item->id,
            'transaction_date' => now()->subMonth(),
            'product_id' => $product->id,
            'size' => 3, 'qty' => 2, 'meters' => 3 * 2,
        ]);
    Inventory::factory()->thaan()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'transaction_date' => now()->subMonth(),
        'product_id' => $product->id,
    ]);

    // Act
    $response = $this->actingAs($user)->get(
        route('stocks.inventories.index', ['unit' => PackingType::Box->value, 'size' => 3])
    );

    // Assert
    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Stock/Inventory/InventoryIndex')
            ->has('items', 1)
            ->where('items.0.size', 3)
            ->where('items.0.qty', 6)
            ->where('items.0.meters', 18)
    );
});

test('inventory filter by product', function () {
    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    $item = FabricReceivingItem::factory()->create(['product_id' => $product->id]);
    Inventory::factory(3)->box()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'transaction_date' => now()->subMonth(),
        'product_id' => $product->id,
        'size' => 3, 'qty' => 2, 'meters' => 3 * 2,
    ]
    );
    Inventory::factory()->thaan()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'transaction_date' => now()->subMonth(),
        'product_id' => $product->id,
    ]);
    Inventory::factory()->suit()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'transaction_date' => now()->subMonth(),
        'product_id' => $product->id,
    ]);
    Inventory::factory()->suit()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'transaction_date' => now()->subMonth(),
        'product_id' => 100,
    ]);

    // Act
    $response = $this->actingAs($user)->get(
        route('stocks.inventories.index', ['name' => $product->name])
    );

    // Assert
    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Stock/Inventory/InventoryIndex')
            ->has('items', 3)
    );
});
