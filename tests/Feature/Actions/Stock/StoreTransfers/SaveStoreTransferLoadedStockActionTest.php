<?php

namespace Tests\Feature\Action\Stock\StoreTransfers;

use App\Actions\Stock\StoreTransfers\SaveStoreTransferLoadedStock;
use App\Enums\PackingType;
use App\Exceptions\InsufficientStockException;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;

test('it splits fifo across multiple lots of the same size and cost', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    $older = Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110,
        'qty' => 2, 'meters' => 42, 'transaction_date' => now()->subDays(2),
    ]);
    $newer = Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110,
        'qty' => 5, 'meters' => 105, 'transaction_date' => now()->subDay(),
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110, 'qty' => 4],
        ],
    ]);

    $this->assertDatabaseCount(StoreTransferItem::class, 1);
    $item = StoreTransferItem::first();
    expect((float) $item->qty)->toBe(4.0)
        ->and((float) $item->total_qty)->toBe(84.0);

    $older->refresh();
    $newer->refresh();
    // the fully-consumed older lot is booked in place
    expect($older->outbound_id)->not->toBeNull();
    expect((int) $older->qty)->toBe(2);
    // the newer lot is only partially consumed, so it's split: the
    // original row keeps the remainder and stays available...
    expect((int) $newer->qty)->toBe(3);
    expect($newer->outbound_id)->toBeNull();
    // ...while a new replicated row holds the booked portion
    $splitOff = Inventory::query()->where('id', '!=', $newer->id)->where('id', '!=', $older->id)->first();
    expect($splitOff)->not->toBeNull()
        ->and((int) $splitOff->qty)->toBe(2)
        ->and($splitOff->outbound_id)->not->toBeNull();
});

test('it drops zero quantity rows without creating items', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 5, 'meters' => 105,
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110, 'qty' => 0],
        ],
    ]);

    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});

test('it throws and rolls back when requested quantity exceeds available stock', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 2, 'meters' => 42,
    ]);

    $handle = fn () => (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110, 'qty' => 5],
        ],
    ]);

    expect($handle)->toThrow(InsufficientStockException::class);
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});

test('it matches a null cost lot when the payload cost is zero', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => null, 'qty' => 4, 'meters' => 84,
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 0, 'qty' => 3],
        ],
    ]);

    $this->assertDatabaseCount(StoreTransferItem::class, 1);
});

test('suit total is cost and expense times meters', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Suit, 'size' => 3, 'cost' => 100, 'qty' => 10, 'meters' => 30,
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Suit->value, 'size' => 3, 'cost' => 100, 'qty' => 4],
        ],
    ]);

    expect((float) StoreTransferItem::first()->total_amount)->toBe(12 * 105.0);
});

test('box total is cost and expense times quantity', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Box, 'size' => 3, 'cost' => 100, 'qty' => 10, 'meters' => 30,
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Box->value, 'size' => 3, 'cost' => 100, 'qty' => 4],
        ],
    ]);

    expect((float) StoreTransferItem::first()->total_amount)->toBe(4 * 105.0);
});

test('thaan total uses the entered meters and books those meters from stock', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    $lot = Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 5, 'meters' => 105,
    ]);

    (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110, 'qty' => 2, 'meters' => 40],
        ],
    ]);

    $item = StoreTransferItem::first();
    expect((float) $item->total_qty)->toBe(40.0)
        ->and((float) $item->total_amount)->toBe(40 * 115.0)
        ->and((float) $lot->refresh()->meters)->toBe(65.0);
});

test('it throws when entered meters exceed available meters', function () {
    $product = Product::factory()->thaan()->create();
    $transfer = StoreTransfer::factory()->storeType()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 5, 'meters' => 105,
    ]);

    $handle = fn () => (new SaveStoreTransferLoadedStock)->handle($transfer, [
        'expense' => 5,
        'rows' => [
            ['product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110, 'qty' => 2, 'meters' => 200],
        ],
    ]);

    expect($handle)->toThrow(InsufficientStockException::class);
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});
