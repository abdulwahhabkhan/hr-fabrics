<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\Purchase\UpdateStockPrice;
use App\Actions\Stock\Conversions\CreateConversion;
use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->product = Product::factory()->thaan()->create();
});

function thaanLot(Product $product, int $qty, float $meters, ?float $cost = 400, StockLocation $location = StockLocation::Shop, ?string $receivedOn = null, ?FabricReceiving $receiving = null): Inventory
{
    return Inventory::factory()->withPurchase($receiving)->create([
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => $qty,
        'size' => 0,
        'meters' => $meters,
        'cost' => $cost,
        'location' => $location,
        'transaction_date' => $receivedOn ?? now()->subWeek(),
    ]);
}

function conversionData(Product $product, array $overrides = []): array
{
    return array_replace_recursive([
        'product_id' => $product->id,
        'location' => StockLocation::Shop->value,
        'from' => ['unit' => 'Thaan', 'qty' => 2, 'size' => 25],
        'to' => ['unit' => 'Suit', 'qty' => 10, 'size' => 5],
    ], $overrides);
}

it('cuts thaan meters into a new suit lot at the same location', function () {
    $lot = thaanLot($this->product, 3, 75);

    $conversion = resolve(CreateConversion::class)->handle(conversionData($this->product), $this->user);

    expect($conversion->location)->toBe(StockLocation::Shop)
        ->and($conversion->transaction_date->isToday())->toBeTrue()
        ->and($conversion->sku)->toBe($this->product->name)
        ->and($lot->refresh())->qty->toBe(1)->meters->toBe(25.0)->outbound_id->toBeNull()
        ->and($conversion->consumedInventories()->sole())->qty->toBe(2)->meters->toBe(50.0);

    $produced = $conversion->producedInventories()->sole();
    expect($produced)
        ->unit->toBe(PackingType::Suit)
        ->size->toBe(5.0)
        ->qty->toBe(10)
        ->meters->toBe(50.0)
        ->cost->toBe(400.0)
        ->location->toBe(StockLocation::Shop)
        ->and($produced->transaction_date->isToday())->toBeTrue()
        ->and(Inventory::query()->available()->where('unit', PackingType::Thaan)->sum('meters'))->toEqual(25);
});

it('consumes thaan lots oldest first and prices the output at their weighted cost', function () {
    $older = thaanLot($this->product, 1, 30, cost: 300, receivedOn: now()->subWeeks(2));
    $newer = thaanLot($this->product, 2, 60, cost: 500);

    $conversion = resolve(CreateConversion::class)->handle(
        conversionData($this->product, ['from' => ['qty' => 2, 'size' => 25], 'to' => ['unit' => 'Thaan', 'qty' => 5, 'size' => 10]]),
        $this->user,
    );

    expect($older->refresh()->outbound_id)->toBe($conversion->id)
        ->and($newer->refresh())->meters->toBe(40.0)->outbound_id->toBeNull()
        ->and($conversion->producedInventories()->sole())
        ->unit->toBe(PackingType::Thaan)
        ->meters->toBe(50.0)
        ->cost->toBe(380.0);
});

it('only cuts thaan stock held at the chosen location', function () {
    thaanLot($this->product, 4, 100, location: StockLocation::WarehouseFSD);
    thaanLot($this->product, 1, 20);

    expect(fn () => resolve(CreateConversion::class)->handle(conversionData($this->product), $this->user))
        ->toThrow(ValidationException::class, 'Only 20 meters available at Shop.')
        ->and(Conversion::query()->exists())->toBeFalse()
        ->and(Inventory::query()->booked()->exists())->toBeFalse()
        ->and(Inventory::query()->count())->toBe(2);
});

it('re-prices converted stock when the source receiving is priced later', function () {
    $receiving = FabricReceiving::factory()->confirmed()->create();
    thaanLot($this->product, 2, 50, cost: null, receiving: $receiving);
    $conversion = resolve(CreateConversion::class)->handle(conversionData($this->product), $this->user);
    $purchase = Purchase::factory()->create(['stock_id' => $receiving->id, 'stock_ids' => (string) $receiving->id]);
    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $this->product->id,
        'unit' => PackingType::Thaan->value,
        'price' => 350,
    ]);

    resolve(UpdateStockPrice::class)->handle($purchase);

    expect($conversion->producedInventories()->sole()->cost)->toBe(350.0);
});
