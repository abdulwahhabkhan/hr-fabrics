<?php

use App\Console\Commands\Product\UpdateAvailabilityCommand;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\User;

use function Pest\Laravel\artisan;

beforeEach(function () {
    User::factory()->create(['name' => 'script']);
    Brand::factory()->create();
});

test('update all products availability to false', function () {
    // Arrange
    Product::factory(3)->available()->create();
    // Action
    artisan(UpdateAvailabilityCommand::class)->assertSuccessful();
    // Assert
    expect(Product::where('is_available', true)->count())->toBe(0);
});

test('update availability to true if available', function () {
    // Arrange
    $products = Product::factory(3)->available()->create();
    $product = $products->random();
    Inventory::factory()
        ->withPurchase()
        ->suit()
        ->create([
            'product_id' => $product->id,
            'size' => $product->size,
        ]);
    // Action
    artisan(UpdateAvailabilityCommand::class)->assertSuccessful();
    // Assert
    $product->refresh();
    expect($product)
        ->is_available->toBeTrue();
});
