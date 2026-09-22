<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\Purchase\UpdateStockPrice;
use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseHas;

test('update the stock inventory price with one to one', function (bool $singleLine) {
    // Arrange
    /** @var FabricReceiving $stock */
    $stock = FabricReceiving::factory()
        ->hasItems($singleLine ? 1 : random_int(2, 3))
        ->confirmed()
        ->create();
    /** @var Purchase $receipt */
    $receipt = Purchase::factory()->create([
        'stock_ids' => $stock->id,
        'stock_id' => $stock->id,
    ]);
    resolve(FabricReceivingConfirmed::class)->handle($stock);
    foreach ($stock->items as $item) {
        $price = fake()->randomNumber(3);
        PurchaseItem::factory()->create([
            'purchase_id' => $receipt->id,
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'size' => $item->size,
            'price' => $price,
            'qty' => $item->qty,
            'total_qty' => $item->total_qty,
            'total' => $item->qty * $price,
        ]);
    }
    // Action
    resolve(UpdateStockPrice::class)->handle($receipt);

    // Assert
    foreach ($receipt->items as $item) {
        assertDatabaseHas(Inventory::class, [
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'cost' => $item->price,
        ]);
    }
})->with([
    'single line' => true,
    'multiple lines' => false,
]);
test('update the stock inventory price with one to many', function (bool $singleLine) {
    // Arrange
    $stocks = FabricReceiving::factory(random_int(2, 3))
        ->hasItems($singleLine ? 1 : random_int(2, 3))
        ->confirmed()
        ->create();
    $stocksIds = $stocks->pluck('id');
    /** @var Purchase $receipt */
    $receipt = Purchase::factory()->create([
        'stock_id' => $stocksIds->first(),
        'stock_ids' => $stocksIds->implode(','),
    ]);
    foreach ($stocks as $stock) {
        resolve(FabricReceivingConfirmed::class)->handle($stock);
        foreach ($stock->items as $item) {
            $price = fake()->randomNumber(3);
            PurchaseItem::factory()->create([
                'purchase_id' => $receipt->id,
                'product_id' => $item->product_id,
                'unit' => $item->unit,
                'size' => $item->size,
                'price' => $price,
                'qty' => $item->qty,
                'total_qty' => $item->total_qty,
                'total' => $item->qty * $price,
            ]);
        }
    }

    // Action
    resolve(UpdateStockPrice::class)->handle($receipt);

    // Assert
    foreach ($receipt->items as $item) {
        assertDatabaseHas(Inventory::class, [
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'cost' => $item->price,
        ]);
    }
})->with([
    'single line' => true,
    'multiple lines' => false,
]);
