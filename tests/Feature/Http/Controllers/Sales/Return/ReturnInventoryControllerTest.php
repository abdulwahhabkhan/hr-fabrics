<?php

use App\Enums\ReturnStatus;
use App\Models\Catalog\Product;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

test('sales return inventory detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.returns.inventory');
    $return = SalesReturn::factory()->closed()->create();
    $product = Product::factory()->create(['name' => 'Test Product']);
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'stockable_id' => $return->id,
        'stockable_type' => $return->getMorphClass(),
    ]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.inventory', $return));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Inventory/StockInventoryView')
        ->has('inventories', 1)
        ->has('inventories.0.product', fn (Assert $page) => $page
            ->where('id', $product->id)
            ->where('name', 'Test Product')
        )
        ->where('inventories.0.reference_no', $return->invoice_no)
        ->where('inventories.0.lot_no', '')
        ->where('back_url', route('sales.returns.index'))
        ->where('page_header', 'Sale Return: Inventory Detail')
    );
});

test('sales return inventory detail is forbidden for an open return', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.returns.inventory');
    $return = SalesReturn::factory()->create(['status' => ReturnStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.inventory', $return));

    // Assert
    $response->assertForbidden();
});

test('sales return inventory detail is forbidden without permission', function () {
    // Arrange
    $user = $this->getAdmin();
    $return = SalesReturn::factory()->closed()->create();

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.inventory', $return));

    // Assert
    $response->assertForbidden();
});
