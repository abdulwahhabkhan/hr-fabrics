<?php

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('brand valuation page loads with required keys', function (): void {
    // Action
    $response = $this->get(route('stocks.value-by-brand', ['type' => '']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Inventory/InventoryValuationByBrand')
        ->has('items')
        ->has('total_value')
        ->has('brands')
        ->has('filters')
    );
});

test('brand valuation includes stock of products without a brand', function (): void {
    // Arrange
    $product = Product::factory()->create(['brand_id' => null]);
    $this->addInventory($product, PackingType::Thaan, 2, 10);

    // Action
    $response = $this->get(route('stocks.value-by-brand', ['type' => '']));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('items', 1)
        ->where('items.0.brand_name', null)
    );
});
