<?php

use App\Console\Commands\Product\RestoreMissingStockCommand;
use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/**
 * @param  array<string, mixed>  $attributes
 */
function receivedLine(PackingType $unit, int $qty, float $size, array $attributes = []): FabricReceivingItem
{
    return FabricReceivingItem::factory()->create([
        'fabric_receiving_id' => FabricReceiving::factory()->confirmed()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'unit' => $unit->value,
        'qty' => $qty,
        'size' => $size,
        'total_qty' => $qty * $size,
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function lineInventory(FabricReceivingItem $item, array $attributes): Inventory
{
    return Inventory::factory()->create([
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => $item->fabric_receiving_id,
        'stockable_item_id' => $item->id,
        'product_id' => $item->product_id,
        'unit' => $item->unit,
        'size' => $item->size,
        'cost' => 548.83,
        'transaction_date' => now(),
        ...$attributes,
    ]);
}

test('adds missing meters to the available row of the line', function (PackingType $unit, int $expectedQty) {
    $item = receivedLine($unit, 24, 16);
    $available = lineInventory($item, ['qty' => 12, 'meters' => 192]);
    lineInventory($item, ['qty' => 6, 'meters' => 96, 'outbound_type' => 'sales.so', 'outbound_id' => 1]);

    artisan(RestoreMissingStockCommand::class)->assertSuccessful();

    assertDatabaseCount(Inventory::class, 2);
    expect($available->refresh())
        ->meters->toEqual(288)
        ->qty->toBe($expectedQty);
})->with([
    'thaan keeps qty' => [PackingType::Thaan, 12],
    'box restores qty' => [PackingType::Box, 18],
]);

test('creates an available row when the whole line is booked', function () {
    $item = receivedLine(PackingType::Thaan, 24, 16);
    lineInventory($item, ['qty' => 24, 'meters' => 192, 'outbound_type' => 'sales.so', 'outbound_id' => 1]);

    artisan(RestoreMissingStockCommand::class)->assertSuccessful();

    assertDatabaseCount(Inventory::class, 2);
    assertDatabaseHas(Inventory::class, [
        'stockable_item_id' => $item->id,
        'outbound_id' => null,
        'qty' => 12,
        'meters' => 192,
        'cost' => 548.83,
    ]);
});

test('dry run does not change inventory', function () {
    $item = receivedLine(PackingType::Thaan, 24, 16);
    $available = lineInventory($item, ['qty' => 24, 'meters' => 192]);

    artisan(RestoreMissingStockCommand::class, ['--dry-run' => true])->assertSuccessful();

    expect($available->refresh()->meters)->toEqual(192);
});

test('skips lines that are complete, over-stocked or not confirmed', function () {
    $complete = receivedLine(PackingType::Thaan, 24, 16);
    $completeRow = lineInventory($complete, ['qty' => 24, 'meters' => 384]);
    $overStocked = receivedLine(PackingType::Thaan, 24, 16);
    $overStockedRow = lineInventory($overStocked, ['qty' => 24, 'meters' => 400]);
    $open = receivedLine(PackingType::Thaan, 24, 16, [
        'fabric_receiving_id' => FabricReceiving::factory()->create()->id,
    ]);
    $openRow = lineInventory($open, ['qty' => 24, 'meters' => 192]);

    artisan(RestoreMissingStockCommand::class)->assertSuccessful();

    assertDatabaseCount(Inventory::class, 3);
    expect($completeRow->refresh()->meters)->toEqual(384)
        ->and($overStockedRow->refresh()->meters)->toEqual(400)
        ->and($openRow->refresh()->meters)->toEqual(192);
});
