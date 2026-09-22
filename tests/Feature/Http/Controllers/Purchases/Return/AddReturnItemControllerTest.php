<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Enums\PackingType;
use App\Enums\ReturnStatus;
use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Stock\Inventory;
use Database\Factories\Catalog\ProductFactory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
    $this->return = PurchaseReturn::factory()->create();
    $this->attachPermissions($this->user, 'purchases.por.update');
});

test('add item to return and allocate exact inventory',
    function (PackingType $packingType, ProductFactory $productFactory) {
        // Arrange
        $qty = fake()->numberBetween(1, 5);
        $size = fake()->numberBetween(1, 10);
        $rate = fake()->numberBetween(10, 100);
        $product = $productFactory->create();
        /** @var Inventory $inventory */
        $inventory = Inventory::factory()
            ->withPurchase()
            ->create([
                'product_id' => $product->id,
                'size' => $size,
                'qty' => $qty,
                'unit' => $packingType->name,
                'cost' => $rate,
            ]);
        $totalQty = $qty * $size;
        $totalAmount = $totalQty * $rate;
        if ($packingType === PackingType::Box) {
            $totalAmount = $rate * $qty;
        }
        $payload = [
            'product_id' => $product->id,
            'unit' => $packingType->name,
            'qty' => $qty,
            'size' => $size,
            'rate' => $rate,
            'total_qty' => $totalQty,
        ];
        // Action
        $response = postJson(route('ajax.return.item.save', $this->return->id), $payload);
        // Assert
        $response->assertJsonMissingValidationErrors()
            ->assertOk();
        assertDatabaseCount(PurchaseReturnItem::class, 1);
        $item = PurchaseReturnItem::first();
        assertDatabaseHas(PurchaseReturnItem::class, [
            'purchase_return_id' => $this->return->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'rate' => $rate,
            'total_qty' => $totalQty,
            'size' => $size,
            'unit' => $packingType->name,
            'total_amount' => $totalAmount,
        ]);

        // inventory should be booked against the return item (no splitting needed)
        assertDatabaseHas(Inventory::class, [
            'id' => $inventory->id,
            'product_id' => $product->id,
            'unit' => $packingType->name,
            'qty' => $qty,
            'outbound_type' => $this->return->getMorphClass(),
            'outbound_id' => $this->return->id,
            'outbound_item_id' => $item->id,
        ]);

        $this->return->refresh();
        expect($this->return)
            ->total_qty->toEqual($totalQty)
            ->total->toEqual($totalAmount);
    })->with([
        'box' => [PackingType::Box, Product::factory()->box()],
        'thaan' => [PackingType::Thaan, Product::factory()->thaan()],
        'suit' => [PackingType::Suit, Product::factory()->suit()],
    ]);

it('allocates partial inventory and splits line', function () {
    // Arrange
    $qtyRequested = 3;
    $size = 5; // meters per unit
    $rate = 20;
    $product = Product::factory()->suit()->create();
    /** @var Inventory $inv */
    $inv = Inventory::factory()
        ->withPurchase()
        ->create([
            'product_id' => $product->id,
            'size' => $size,
            'qty' => 10, // more than requested
            'unit' => PackingType::Suit->name,
            'cost' => $rate,
        ]);

    $totalQty = $qtyRequested * $size;
    $totalAmount = $totalQty * $rate;

    $payload = [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => $qtyRequested,
        'size' => $size,
        'rate' => $rate,
        'total_qty' => $totalQty,
    ];

    // Action
    $response = postJson(route('ajax.return.item.save', $this->return->id), $payload);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    $item = PurchaseReturnItem::first();

    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => $qtyRequested,
        'outbound_type' => $this->return->getMorphClass(),
        'outbound_id' => $this->return->id,
        'outbound_item_id' => $item->id,
    ]);

    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => 10 - $qtyRequested,
        'outbound_type' => null,
        'outbound_id' => null,
        'outbound_item_id' => null,
    ]);
});

it('allocates across multiple inventory lines in fifo order', function () {
    // Arrange
    $product = Product::factory()->suit()->create();
    $rate = 20;
    /** @var Inventory $older */
    $older = Inventory::factory()
        ->withPurchase()
        ->create([
            'product_id' => $product->id,
            'size' => 5,
            'qty' => 2,
            'unit' => PackingType::Suit->name,
            'cost' => $rate,
            'transaction_date' => now()->subDay(),
        ]);
    /** @var Inventory $newer */
    $newer = Inventory::factory()
        ->withPurchase()
        ->create([
            'product_id' => $product->id,
            'size' => 5,
            'qty' => 5,
            'unit' => PackingType::Suit->name,
            'cost' => $rate,
            'transaction_date' => now(),
        ]);

    $qtyRequested = 4;
    $payload = [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => $qtyRequested,
        'size' => 5,
        'rate' => $rate,
        'total_qty' => $qtyRequested * 5,
    ];

    // Action
    $response = postJson(route('ajax.return.item.save', $this->return->id), $payload);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    $item = PurchaseReturnItem::first();

    // Older line fully consumed (fifo)
    assertDatabaseHas(Inventory::class, [
        'id' => $older->id,
        'qty' => 2,
        'outbound_type' => $this->return->getMorphClass(),
        'outbound_id' => $this->return->id,
        'outbound_item_id' => $item->id,
    ]);

    // Remaining 2 booked from the newer line, rest (3) left available
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => $qtyRequested - 2,
        'outbound_type' => $this->return->getMorphClass(),
        'outbound_id' => $this->return->id,
        'outbound_item_id' => $item->id,
    ]);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => 5 - 2,
        'outbound_type' => null,
        'outbound_id' => null,
        'outbound_item_id' => null,
    ]);
});

it('rejects the request when available stock cannot fulfil the requested quantity', function () {
    // Arrange
    $product = Product::factory()->suit()->create();
    Inventory::factory()
        ->withPurchase()
        ->create([
            'product_id' => $product->id,
            'size' => 5,
            'qty' => 2,
            'unit' => PackingType::Suit->name,
            'cost' => 20,
        ]);

    $payload = [
        'product_id' => $product->id,
        'unit' => PackingType::Suit->name,
        'qty' => 5,
        'size' => 5,
        'rate' => 20,
        'total_qty' => 25,
    ];

    // Action
    $response = postJson(route('ajax.return.item.save', $this->return->id), $payload);

    // Assert
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('product');
    assertDatabaseCount(PurchaseReturnItem::class, 0);
});

it('fails validation when required fields are missing', function () {
    // Action
    $response = postJson(route('ajax.return.item.save', $this->return->id), []);

    // Assert
    $response->assertJsonValidationErrors(['qty', 'unit', 'rate', 'product_id']);
    assertDatabaseCount(PurchaseReturnItem::class, 0);
});

it('forbids adding an item without the update permission', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $this->actingAs($user);

    $payload = [
        'product_id' => Product::factory()->suit()->create()->id,
        'unit' => PackingType::Suit->name,
        'qty' => 1,
        'size' => 5,
        'rate' => 20,
        'total_qty' => 5,
    ];

    // Action
    $response = postJson(route('ajax.return.item.save', $this->return->id), $payload);

    // Assert
    $response->assertForbidden();
});

it('forbids adding an item to a return that is not open', function () {
    // Arrange
    $return = PurchaseReturn::factory()->create(['status' => ReturnStatus::Closed]);
    $payload = [
        'product_id' => Product::factory()->suit()->create()->id,
        'unit' => PackingType::Suit->name,
        'qty' => 1,
        'size' => 5,
        'rate' => 20,
        'total_qty' => 5,
    ];

    // Action
    $response = postJson(route('ajax.return.item.save', $return->id), $payload);

    // Assert
    $response->assertForbidden();
});
