<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Stock\StockTransfers\ConfirmStockTransfer;
use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->product = Product::factory()->create();
});

function stockLot(Product $product, PackingType $unit, int $qty, float $meters, float $size, StockLocation $location = StockLocation::Shop, ?string $receivedOn = null): Inventory
{
    return Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => $unit,
        'qty' => $qty,
        'size' => $size,
        'meters' => $meters,
        'cost' => 450,
        'location' => $location,
        'transaction_date' => $receivedOn ?? now()->subWeek(),
    ]);
}

it('moves a whole lot to the destination keeping its source and cost', function () {
    $lot = stockLot($this->product, PackingType::Box, 4, 22, 5.5);
    $transfer = StockTransfer::factory()->create([
        'product_id' => $this->product->id, 'qty' => 4, 'size' => 5.5, 'meters' => 22,
    ]);

    resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user);

    $lot->refresh();
    expect($transfer->status)->toBe(StatusText::Close)
        ->and($transfer->transaction_date->isToday())->toBeTrue()
        ->and($lot->outbound_type)->toBe($transfer->getMorphClass())
        ->and($lot->outbound_id)->toBe($transfer->id);

    $arrived = Inventory::query()->available()->sole();
    expect($arrived->location)->toBe(StockLocation::WarehouseFSD)
        ->and($arrived->parent_id)->toBe($lot->id)
        ->and($arrived->stockable_type)->toBe($lot->stockable_type)
        ->and($arrived->stockable_id)->toBe($lot->stockable_id)
        ->and($arrived->cost)->toBe(450.0)
        ->and($arrived->qty)->toBe(4)
        ->and($arrived->meters)->toBe(22.0);
});

it('splits a lot when only part of it moves', function () {
    $lot = stockLot($this->product, PackingType::Box, 5, 27.5, 5.5);
    $transfer = StockTransfer::factory()->create([
        'product_id' => $this->product->id, 'qty' => 2, 'size' => 5.5, 'meters' => 11,
    ]);

    resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user);

    expect($lot->refresh())
        ->qty->toBe(3)
        ->meters->toBe(16.5)
        ->outbound_id->toBeNull()
        ->and($transfer->inventories()->sole())->qty->toBe(2)->meters->toBe(11.0)
        ->and(Inventory::query()->available()->atLocation(StockLocation::WarehouseFSD)->sole())->qty->toBe(2)->meters->toBe(11.0);
});

it('moves thaan stock by meters across lots oldest first', function () {
    $older = stockLot($this->product, PackingType::Thaan, 1, 40, 0, receivedOn: now()->subWeeks(2));
    $newer = stockLot($this->product, PackingType::Thaan, 2, 90, 0);
    $transfer = StockTransfer::factory()->thaan(meters: 70, qty: 2)->create(['product_id' => $this->product->id]);

    resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user);

    expect($older->refresh()->outbound_id)->toBe($transfer->id)
        ->and($newer->refresh()->meters)->toBe(60.0)
        ->and($transfer->inventories()->sum('meters'))->toEqual(70)
        ->and(Inventory::query()->available()->atLocation(StockLocation::WarehouseFSD)->sum('meters'))->toEqual(70)
        ->and(Inventory::query()->available()->atLocation(StockLocation::Shop)->sum('meters'))->toEqual(60);
});

it('moves only the transferred thaan count when a whole lot is used up', function () {
    stockLot($this->product, PackingType::Thaan, 17, 17, 0, receivedOn: now()->subWeeks(2));
    $newer = stockLot($this->product, PackingType::Thaan, 17, 289, 0);
    $transfer = StockTransfer::factory()->thaan(meters: 44, qty: 2)->create(['product_id' => $this->product->id]);

    resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user);

    $arrived = Inventory::query()->available()->atLocation(StockLocation::WarehouseFSD);
    expect((int) $arrived->clone()->sum('qty'))->toBe(2)
        ->and((float) $arrived->clone()->sum('meters'))->toBe(44.0)
        ->and((int) $transfer->inventories()->sum('qty'))->toBe(2)
        ->and($newer->refresh()->meters)->toBe(262.0);
});

it('does not use stock held at another location', function () {
    stockLot($this->product, PackingType::Box, 5, 27.5, 5.5, StockLocation::WarehouseFSD);
    $transfer = StockTransfer::factory()->create([
        'product_id' => $this->product->id, 'qty' => 2, 'size' => 5.5, 'meters' => 11,
    ]);

    expect(fn () => resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user))
        ->toThrow(ValidationException::class, 'Only 0 Box of size 5.5 available at Shop.')
        ->and($transfer->refresh()->status)->toBe(StatusText::Open)
        ->and(Inventory::query()->count())->toBe(1);
});

it('refuses to confirm a transfer twice', function () {
    $transfer = StockTransfer::factory()->confirmed()->create(['product_id' => $this->product->id]);

    expect(fn () => resolve(ConfirmStockTransfer::class)->handle($transfer, $this->user))
        ->toThrow(ValidationException::class, 'Only an open transfer can be confirmed.');
});
