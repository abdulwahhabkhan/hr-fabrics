<?php

use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Facades\Permission;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    Permission::fake(['stocks.stock-transfers.*' => true]);
    $this->actingAs($this->getAdmin());
    $this->product = Product::factory()->create();
    $this->lot = Inventory::factory()->withPurchase()->create([
        'product_id' => $this->product->id,
        'unit' => PackingType::Box,
        'qty' => 5,
        'size' => 5.5,
        'meters' => 27.5,
        'location' => StockLocation::Shop,
    ]);
    $this->payload = [
        'product_id' => $this->product->id,
        'unit' => PackingType::Box->value,
        'size' => 5.5,
        'qty' => 2,
        'from_location' => StockLocation::Shop->value,
        'to_location' => StockLocation::WarehouseFSD->value,
    ];
});

test('index lists transfers filtered by location', function () {
    StockTransfer::factory()->create(['product_id' => $this->product->id]);
    StockTransfer::factory()->create([
        'product_id' => $this->product->id,
        'from_location' => StockLocation::WarehouseFSD,
        'to_location' => StockLocation::WarehouseFSD,
    ]);

    $this->get(route('stocks.stock-transfers.index', ['location' => StockLocation::Shop->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Stock/StockTransfers/StockTransferIndex')
            ->has('rows.data', 1)
            ->has('stockLocations', 2)
        );
});

test('create page lists products and locations', function () {
    $this->get(route('stocks.stock-transfers.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Stock/StockTransfers/StockTransferForm')
            ->has('stockLocations', 2)
            ->has('products')
        );
});

test('store saves a draft without moving stock', function () {
    $this->post(route('stocks.stock-transfers.store'), $this->payload)
        ->assertRedirect(route('stocks.stock-transfers.index'));

    assertDatabaseHas(StockTransfer::class, [
        'product_id' => $this->product->id,
        'qty' => 2,
        'meters' => 11,
        'status' => StatusText::Open->value,
    ]);
    expect($this->lot->refresh()->meters)->toBe(27.5);
});

test('store with confirm moves the stock', function () {
    $this->post(route('stocks.stock-transfers.store'), [...$this->payload, 'confirm' => true])
        ->assertRedirect(route('stocks.stock-transfers.index'));

    expect(StockTransfer::query()->sole()->status)->toBe(StatusText::Close)
        ->and(Inventory::query()->available()->atLocation(StockLocation::WarehouseFSD)->sum('meters'))->toEqual(11);
});

test('store rejects moving more than the source location holds', function () {
    $this->post(route('stocks.stock-transfers.store'), [...$this->payload, 'qty' => 6])
        ->assertSessionHasErrors(['qty' => 'Only 5 Box of size 5.5 available at Shop.']);

    assertDatabaseCount(StockTransfer::class, 0);
});

test('store rejects the same source and destination', function () {
    $this->post(route('stocks.stock-transfers.store'), [...$this->payload, 'to_location' => StockLocation::Shop->value])
        ->assertSessionHasErrors(['to_location' => 'Stock must move to a different location.']);
});

test('update changes an open transfer', function () {
    $transfer = StockTransfer::factory()->create(['product_id' => $this->product->id]);

    $this->put(route('stocks.stock-transfers.update', $transfer), [...$this->payload, 'qty' => 3])
        ->assertRedirect(route('stocks.stock-transfers.index'));

    expect($transfer->refresh())->qty->toBe(3)->meters->toBe(16.5);
});

test('a confirmed transfer cannot be edited, updated or deleted', function () {
    $transfer = StockTransfer::factory()->confirmed()->create(['product_id' => $this->product->id]);

    $this->get(route('stocks.stock-transfers.edit', $transfer))->assertRedirect(route('stocks.stock-transfers.index'));
    $this->put(route('stocks.stock-transfers.update', $transfer), $this->payload)->assertForbidden();
    $this->delete(route('stocks.stock-transfers.destroy', $transfer))->assertForbidden();
});

test('confirm moves the stock of an open transfer', function () {
    $transfer = StockTransfer::factory()->create([
        'product_id' => $this->product->id, 'qty' => 2, 'size' => 5.5, 'meters' => 11,
    ]);

    $this->post(route('stocks.stock-transfers.confirm', $transfer))
        ->assertRedirect(route('stocks.stock-transfers.index'));

    expect($transfer->refresh()->status)->toBe(StatusText::Close);
});

test('destroy deletes an open transfer', function () {
    $transfer = StockTransfer::factory()->create(['product_id' => $this->product->id]);

    $this->delete(route('stocks.stock-transfers.destroy', $transfer))
        ->assertRedirect(route('stocks.stock-transfers.index'));

    assertSoftDeleted($transfer);
});

test('availability returns stock per location', function () {
    Inventory::factory()->withPurchase()->create([
        'product_id' => $this->product->id,
        'unit' => PackingType::Box,
        'qty' => 1,
        'size' => 5.5,
        'meters' => 5.5,
        'location' => StockLocation::WarehouseFSD,
    ]);

    $this->getJson(route('stocks.stock-transfers.availability', $this->product))
        ->assertOk()
        ->assertExactJson(['stock' => [
            ['location' => 'shop', 'unit' => 'Box', 'size' => 5.5, 'qty' => 5, 'meters' => 27.5],
            ['location' => 'warehouse_fsd', 'unit' => 'Box', 'size' => 5.5, 'qty' => 1, 'meters' => 5.5],
        ]]);
});

test('unlock reverses a transfer confirmed today', function () {
    $this->post(route('stocks.stock-transfers.store'), [...$this->payload, 'confirm' => true]);
    $transfer = StockTransfer::query()->sole();

    $this->post(route('actions.stock-transfer.open', $transfer))
        ->assertRedirect(route('stocks.stock-transfers.edit', $transfer));

    expect($transfer->refresh()->status)->toBe(StatusText::Open)
        ->and($this->lot->refresh()->outbound_id)->toBeNull();
});

test('unlock is refused for a transfer confirmed on an earlier day', function () {
    $transfer = StockTransfer::factory()->confirmed()->create([
        'product_id' => $this->product->id,
        'confirmed_at' => now()->subDay(),
    ]);

    $this->post(route('actions.stock-transfer.open', $transfer))->assertForbidden();
});
