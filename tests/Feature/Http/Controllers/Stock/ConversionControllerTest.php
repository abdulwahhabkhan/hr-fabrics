<?php

use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

test('conversion listing loaded', function () {
    $user = $this->getAdmin();
    Conversion::factory()->count(10)->create();

    $response = $this->actingAs($user)
        ->get(route('stocks.conversions.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Conversion/ConversionIndex')
        ->has('items.data', 10)
        ->where('items.data.0.location', StockLocation::Shop->value)
        ->where('items.data.0.leftover_meters', 0)
        ->has('filters')
        ->where('canAdd', true)
    );
});

test('conversion listing filters by location', function () {
    $user = $this->getAdmin();
    Conversion::factory()->count(2)->create();
    Conversion::factory()->create(['location' => StockLocation::WarehouseFSD]);

    $response = $this->actingAs($user)
        ->get(route('stocks.conversions.index', ['location' => StockLocation::WarehouseFSD->value]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('items.data', 1)
        ->where('items.data.0.location', StockLocation::WarehouseFSD->value)
    );
});

test('conversion form loaded', function () {
    $user = $this->getAdmin();

    $response = $this->actingAs($user)
        ->get(route('stocks.conversions.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Conversion/ConversionForm')
        ->has('products')
    );
});

test('create thaan to suit conversion', function () {
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => 2,
        'size' => 0,
        'meters' => 50,
        'cost' => 400,
    ]);

    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), [
            'product_id' => $product->id,
            'location' => StockLocation::Shop->value,
            'from' => ['unit' => 'Thaan', 'size' => 25, 'qty' => 2],
            'to' => ['unit' => 'Suit', 'size' => 5, 'qty' => 10],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('stocks.conversions.index'));
    $conversion = Conversion::query()->sole();
    expect($conversion)
        ->product_id->toBe($product->id)
        ->created_by->toBe($user->id)
        ->and($conversion->from->all())->toBe(['unit' => 'Thaan', 'size' => 25, 'qty' => 2])
        ->and($conversion->to->all())->toBe(['unit' => 'Suit', 'size' => 5, 'qty' => 10]);
    $this->assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'stockable_type' => $conversion->getMorphClass(),
        'stockable_id' => $conversion->id,
        'unit' => PackingType::Suit->value,
        'qty' => 10,
        'meters' => 50,
        'outbound_id' => null,
    ]);
});

test('conversion must produce exactly the meters it cuts', function (int $producedQty, string $message) {
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => 1,
        'size' => 0,
        'meters' => 16,
    ]);

    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), [
            'product_id' => $product->id,
            'location' => StockLocation::Shop->value,
            'from' => ['unit' => 'Thaan', 'size' => 16, 'qty' => 1],
            'to' => ['unit' => 'Suit', 'size' => 2, 'qty' => $producedQty],
        ]);

    $response->assertSessionHasErrors(['to.qty' => $message]);
    expect(Conversion::query()->exists())->toBeFalse()
        ->and(Inventory::query()->booked()->exists())->toBeFalse();
})->with([
    'leftover' => [4, 'Produced meters (8) must equal the thaan meters being cut (16), leftover is not allowed.'],
    'more than cut' => [9, 'Produced meters (18) must equal the thaan meters being cut (16), leftover is not allowed.'],
]);

test('conversion requires its fields', function () {
    $user = $this->getAdmin();

    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), []);

    $response->assertSessionHasErrors(['product_id', 'location', 'from.qty', 'from.size', 'to.unit', 'to.qty', 'to.size']);
});

test('conversion without enough thaan stock is refused', function () {
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();

    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), [
            'product_id' => $product->id,
            'location' => StockLocation::Shop->value,
            'from' => ['unit' => 'Thaan', 'size' => 25, 'qty' => 2],
            'to' => ['unit' => 'Suit', 'size' => 5, 'qty' => 10],
        ]);

    $response->assertSessionHasErrors(['from.qty' => 'Only 0 meters available at Shop.']);
    expect(Conversion::query()->exists())->toBeFalse();
});

test('delete conversion', function () {
    $user = $this->getAdmin();
    $conversion = Conversion::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('stocks.conversions.destroy', $conversion->id));

    $response->assertRedirect(route('stocks.conversions.index'));
    $this->assertSoftDeleted($conversion);
});
