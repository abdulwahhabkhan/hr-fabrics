<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Stock\StockTransfers\ConfirmStockTransfer;
use App\Actions\Stock\StockTransfers\UnlockStockTransfer;
use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $product = Product::factory()->create();
    $this->lot = Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => PackingType::Box,
        'qty' => 5,
        'size' => 5.5,
        'meters' => 27.5,
        'location' => StockLocation::Shop,
    ]);
    $this->transfer = StockTransfer::factory()->create([
        'product_id' => $product->id, 'qty' => 2, 'size' => 5.5, 'meters' => 11,
    ]);
    resolve(ConfirmStockTransfer::class)->handle($this->transfer, $this->user);
});

it('reverses a confirmed transfer', function () {
    resolve(UnlockStockTransfer::class)->handle($this->transfer, $this->user);

    expect($this->transfer->status)->toBe(StatusText::Open)
        ->and($this->transfer->confirmed_at)->toBeNull()
        ->and(Inventory::query()->atLocation(StockLocation::WarehouseFSD)->exists())->toBeFalse()
        ->and(Inventory::query()->available()->atLocation(StockLocation::Shop)->sum('meters'))->toEqual(27.5)
        ->and($this->transfer->inventories()->exists())->toBeFalse();
});

it('refuses to unlock once the moved stock has been sold', function () {
    $order = Order::factory()->create();
    Inventory::query()->atLocation(StockLocation::WarehouseFSD)->update([
        'outbound_type' => $order->getMorphClass(),
        'outbound_id' => $order->id,
    ]);

    expect(fn () => resolve(UnlockStockTransfer::class)->handle($this->transfer, $this->user))
        ->toThrow(ValidationException::class, 'has already been used')
        ->and($this->transfer->refresh()->status)->toBe(StatusText::Close);
});
