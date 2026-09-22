<?php

use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Services\ProductService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = new ProductService;
});

it('returns products ordered by name with their brand name', function () {
    $brand = Brand::factory()->create(['name' => 'Acme']);
    Product::factory()->create(['name' => 'Zebra', 'brand_id' => $brand->id]);
    Product::factory()->create(['name' => 'Alpha', 'brand_id' => $brand->id]);

    $products = $this->service->autocompleteProducts();

    expect($products->pluck('name')->all())->toBe(['Alpha', 'Zebra'])
        ->and($products->first()->brand_name)->toBe('Acme');
});

it('caches the autocomplete product list', function () {
    Product::factory()->create(['name' => 'Cached Product']);

    expect(Cache::has(Product::class))->toBeFalse();

    $this->service->autocompleteProducts();

    expect(Cache::has(Product::class))->toBeTrue();
});

it('returns products with their most recent positive purchase price', function () {
    $product = Product::factory()->create(['name' => 'Priced Product']);
    $receipt = Purchase::factory()->create();
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $product->id,
        'price' => 0,
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $product->id,
        'price' => 250,
    ]);

    $products = $this->service->getPORProducts();

    expect($products->firstWhere('id', $product->id)->purchased_price)->toEqual(250);
});

it('leaves the purchase price null when the product has no receipt history', function () {
    $product = Product::factory()->create(['name' => 'Unpriced Product']);

    $products = $this->service->getPORProducts();

    expect($products->firstWhere('id', $product->id)->purchased_price)->toBeNull();
});

it('only returns products that have available inventory', function () {
    $brand = Brand::factory()->create();
    $available = Product::factory()->create(['name' => 'Available Product', 'brand_id' => $brand->id]);
    $unavailable = Product::factory()->create(['name' => 'Unavailable Product', 'brand_id' => $brand->id]);
    $this->addInventory($available, \App\Enums\PackingType::Thaan, 2, 10);

    $products = $this->service->availableProducts();

    expect($products->pluck('id'))->toContain($available->id)
        ->not->toContain($unavailable->id);
});

it('caches the available products list', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $this->addInventory($product, \App\Enums\PackingType::Thaan, 2, 10);

    expect(Cache::has(Product::$availableCacheKey))->toBeFalse();

    $this->service->availableProducts();

    expect(Cache::has(Product::$availableCacheKey))->toBeTrue();
});
