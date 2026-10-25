<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Enums\StockLocation;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;

it('adds received stock at the location chosen for each item', function () {
    $this->getAdmin();
    $receiving = FabricReceiving::factory()->confirmed()->create();
    $shopItem = FabricReceivingItem::factory()->create(['fabric_receiving_id' => $receiving->id]);
    $warehouseItem = FabricReceivingItem::factory()->create([
        'fabric_receiving_id' => $receiving->id,
        'location' => StockLocation::WarehouseFSD,
    ]);

    resolve(FabricReceivingConfirmed::class)->handle($receiving);

    $locations = $receiving->inventories()->pluck('location', 'stockable_item_id');
    expect($locations[$shopItem->id])->toBe(StockLocation::Shop)
        ->and($locations[$warehouseItem->id])->toBe(StockLocation::WarehouseFSD);
});
