<?php

namespace Tests\Feature\Action\Stock\StoreTransfers;

use App\Actions\Stock\StoreTransfers\GetAvailableStockByCost;
use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;

test('it groups available inventory by size and cost', function () {
    $product = Product::factory()->thaan()->create();

    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 3, 'meters' => 63,
    ]);
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 2, 'meters' => 42,
    ]);
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 28, 'cost' => null, 'qty' => 4, 'meters' => 112,
    ]);
    // booked (not available) row must be excluded
    $booked = Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id, 'unit' => PackingType::Thaan, 'size' => 21, 'cost' => 110, 'qty' => 9, 'meters' => 189,
    ]);
    $booked->outbound()->associate(StoreTransfer::factory()->storeType()->create());
    $booked->outbound_item_id = 1;
    $booked->save();

    $rows = (new GetAvailableStockByCost)->handle($product->id, PackingType::Thaan->value);

    expect($rows)->toHaveCount(2);
    $sizeTwentyOne = $rows->firstWhere('size', 21);
    $sizeTwentyEight = $rows->firstWhere('size', 28);
    expect((float) $sizeTwentyOne->available_qty)->toBe(5.0)
        ->and((float) $sizeTwentyEight->available_qty)->toBe(4.0)
        ->and((float) $sizeTwentyEight->cost)->toBe(0.0);
});
