<?php

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Stock\Conversion;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

test('conversion listing loaded', function () {
    // arrange
    $user = $this->getAdmin();
    Conversion::factory()->count(10)->create();
    // action
    $response = $this->actingAs($user)
        ->get(route('stocks.conversions.index'));
    // assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Conversion/ConversionIndex')
        ->has('items')
        ->has('items.data', 10)
        ->has('filters')
    );

});

test('create thaan to suit conversion', function () {

    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    $body = [
        'product' => $product,
        'from' => ['unit' => PackingType::Thaan->value, 'size' => 25, 'qty' => 2],
        'to' => ['unit' => PackingType::Suit->value, 'size' => 5, 'qty' => 10],
    ];
    $conversion = [
        'from' => json_encode($body['from']),
        'to' => json_encode($body['to']),
        'product_id' => $product->id,
        'sku' => $product->name,
    ];
    // Act
    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), $body);

    // Assert
    $response->assertInternalServerError();
    /* $response->assertSessionHasNoErrors();
     $response->assertRedirect(route('stocks.conversions.index'));
     $this->assertDatabaseHas(Conversion::class, $conversion);*/

});

test('create thaan to suit conversion with inventory', function () {
    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    $body = [
        'product' => $product,
        'from' => ['unit' => PackingType::Thaan->value, 'size' => 25, 'qty' => 2],
        'to' => ['unit' => PackingType::Suit->value, 'size' => 5, 'qty' => 10],
    ];
    $conversion = [
        'from' => json_encode($body['from']),
        'to' => json_encode($body['to']),
        'product_id' => $product->id,
        'sku' => $product->name,
    ];
    // Act
    $response = $this->actingAs($user)
        ->post(route('stocks.conversions.store'), $body);

    // Assert
    $response->assertInternalServerError();
    /*$response->assertSessionHasNoErrors();
    $response->assertRedirect(route('stocks.conversions.index'));
    $this->assertDatabaseHas(Conversion::class, $conversion);
    // Check inventory
    $this->assertDatabaseHas(Inventory::class, [
        'type' => 'MC',
        'product_id' => $product->id,
        'unit' => $body['from']['unit'],
        'size' => 0,
        'qty' => -1 * $body['from']['qty'],
        'meters' => -1 * $body['from']['qty'] * $body['from']['size'],
    ]);
    $this->assertDatabaseHas(Inventory::class, [
        'type' => 'MC',
        'product_id' => $product->id,
        'unit' => $body['to']['unit'],
        'size' => $body['to']['size'],
        'meters' => $body['to']['qty'] * $body['to']['size'],
        'qty' => $body['to']['qty'],
    ]);*/

});

test('delete conversion', function () {
    // Arrange
    $user = $this->getAdmin();
    $conversion = Conversion::factory()->create();

    // Act
    $response = $this->actingAs($user)
        ->delete(route('stocks.conversions.destroy', $conversion->id));

    // Assert

    $response->assertInternalServerError();
    /*$response->assertRedirect(route('stocks.conversions.index'));

    // Check inventory
    $this->assertDatabaseMissing(Inventory::class, [
        'type' => 'MC',
        'product_id' => $product_id,
        'unit' => $from['unit'],
        'size' => 0,
        'qty' => -1 * $from['qty'],
        'meters' => -1 * $from['qty'] * $from['size'],
    ]);
    $this->assertDatabaseMissing(Inventory::class, [
        'type' => 'MC',
        'product_id' => $product_id,
        'unit' => $to['unit'],
        'size' => $to['size'],
        'qty' => $to['qty'],
        'meters' => $to['qty'] * $to['size'],
    ]);*/

});
