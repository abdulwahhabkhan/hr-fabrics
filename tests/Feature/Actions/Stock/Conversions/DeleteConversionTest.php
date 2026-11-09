<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Stock\Conversions\CreateConversion;
use App\Actions\Stock\Conversions\DeleteConversion;
use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => 3,
        'size' => 0,
        'meters' => 75,
        'cost' => 400,
        'location' => StockLocation::Shop,
    ]);
    $this->conversion = resolve(CreateConversion::class)->handle([
        'product_id' => $product->id,
        'location' => StockLocation::Shop->value,
        'from' => ['unit' => 'Thaan', 'qty' => 2, 'size' => 25],
        'to' => ['unit' => 'Suit', 'qty' => 10, 'size' => 5],
    ], $user);
});

it('removes the produced stock and puts the thaans back', function () {
    resolve(DeleteConversion::class)->handle($this->conversion);

    expect($this->conversion->trashed())->toBeTrue()
        ->and(Inventory::query()->where('unit', PackingType::Suit)->exists())->toBeFalse()
        ->and(Inventory::query()->booked()->exists())->toBeFalse()
        ->and(Inventory::query()->available()->where('unit', PackingType::Thaan)->sum('meters'))->toEqual(75);
});

it('refuses to delete once the produced stock has been sold', function () {
    $order = Order::factory()->create();
    Inventory::query()->where('unit', PackingType::Suit)->update([
        'outbound_type' => $order->getMorphClass(),
        'outbound_id' => $order->id,
    ]);

    expect(fn () => resolve(DeleteConversion::class)->handle($this->conversion))
        ->toThrow(ValidationException::class, 'has already been used')
        ->and($this->conversion->refresh()->trashed())->toBeFalse()
        ->and(Inventory::query()->where('unit', PackingType::Suit)->exists())->toBeTrue()
        ->and($this->conversion->consumedInventories()->exists())->toBeTrue();
});
