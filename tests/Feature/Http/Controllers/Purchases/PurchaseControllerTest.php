<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\Purchase\UpdateStockPrice;
use App\Enums\JournalHead;
use App\Enums\StatusText;
use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\partialMock;

beforeEach(function (): void {
    PermissionFacade::fake(['purchases.pos.*' => true]);
});

test('list purchase vouchers', function () {
    // Arrange
    $user = $this->getAdmin();
    Purchase::factory()->count(3)->create();

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Purchases/PurchaseIndex')
        ->has('rows.data', 3)
    );
});

test('filter list purchase vouchers', function () {
    // Arrange
    $user = $this->getAdmin();
    Purchase::factory()->count(2)->create();
    Purchase::factory()->create(['bill_no' => 'B-FILTERED']);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.index', ['bill_no' => 'B-FILTERED']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Purchases/PurchaseIndex')
        ->has('rows.data', 1)
    );
});

test('create purchase voucher page lists confirmed uninvoiced stocks', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->confirmed()->create(['invoiced' => false]);
    FabricReceiving::factory()->create(['invoiced' => false]);
    FabricReceiving::factory()->confirmed()->create(['invoiced' => true]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Purchases/PurchaseFormNew')
        ->has('stocks', 1)
        ->where('stocks.0.id', $stock->id)
    );
});

test('edit purchase voucher page loads form', function () {
    // Arrange
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->has(PurchaseItem::factory()->count(2), 'items')->create();

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.edit', $receipt->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Purchases/PurchaseForm')
        ->has('receipt')
        ->has('items.data', 2)
    );
});

test('show purchase voucher forbidden without permission', function () {
    // Arrange
    PermissionFacade::fake(['purchases.pos.*' => false]);
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.show', $receipt->id));

    // Assert
    $response->assertForbidden();
});

test('edit purchase voucher forbidden without permission', function () {
    // Arrange
    PermissionFacade::fake(['purchases.pos.*' => false]);
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.edit', $receipt->id));

    // Assert
    $response->assertForbidden();
});

test('update purchase voucher forbidden once receipt is closed', function () {
    // Arrange
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->confirmed()->create();

    // Act
    $response = $this->actingAs($user)->put(route('purchases.pos.update', $receipt->id), ['bill_no' => 'V-100']);

    // Assert
    $response->assertForbidden();
});

test('delete purchase voucher forbidden once receipt is closed', function () {
    // Arrange
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->confirmed()->create();

    // Act
    $response = $this->actingAs($user)->delete(route('purchases.pos.destroy', $receipt->id));

    // Assert
    $response->assertForbidden();
});

test('list order items', function () {
    // Arrange
    $user = $this->getAdmin();
    $receipt = Purchase::factory()->has(PurchaseItem::factory()->count(3), 'items')->create();

    // Act
    $response = $this->actingAs($user)->getJson(route('ajax.po.items', $receipt->id));

    // Assert
    $response->assertOk();
    $response->assertJsonCount(3, 'items');
});

test('add order item requires voucher_no', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->create();
    $product = Product::factory()->create();
    $body = [
        'product_id' => $product->id,
        'unit' => 'Box',
        'qty' => 10,
        'price' => 100,
    ];

    // Act
    $response = $this->actingAs($user)->postJson(route('ajax.po.item', $stock->id), $body);

    // Assert
    $response->assertJsonValidationErrors('voucher_no');
});

test('create voucher from multiple pos', function () {
    // Arrange
    $user = $this->getAdmin();
    $stocks = FabricReceiving::factory()
        ->count(2)
        ->hasItems(random_int(1, 4))
        ->create(['status' => 'Close']);
    $stock = $stocks->first();
    $stock_2 = $stocks[1];
    $items = FabricReceivingItem::query()->whereIn('fabric_receiving_id', [$stock->id, $stock_2->id])->get();

    // Action
    $response = $this->actingAs($user)->post(
        route('purchases.pos.store'),
        [
            'stock' => [
                [
                    'id' => $stock->id,
                    'supplier_id' => $stock->supplier_id,
                    'bilti_no' => $stock->bilti_no,
                    'invoice_no' => $stock->invoice_no,
                    'lot_no' => $stock->lot_no,
                ],
                [
                    'id' => $stock_2->id,
                    'supplier_id' => $stock_2->supplier_id,
                    'bilti_no' => $stock_2->bilti_no,
                    'invoice_no' => $stock_2->invoice_no,
                    'lot_no' => $stock_2->lot_no,
                ],
            ],

        ]
    );

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Purchase::class, [
        'stock_id' => $stock->id,
        'stock_ids' => $stocks->pluck('id')->implode(','),
        'invoice_no' => $stocks->pluck('invoice_no')->implode(', '),
        'bilti_no' => $stocks->pluck('bilti_no')->implode(', '),
        'lot_no' => $stocks->pluck('lot_no')->implode(', '),
        'supplier_id' => $stock->supplier_id,
    ]);
    foreach ($items as $item) {
        $this->assertDatabaseHas(PurchaseItem::class, [
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'size' => $item->size,
            'qty' => $item->qty,
            'total_qty' => $item->total_qty,
            'price' => '0',
        ]);
    }
});

test('create voucher from single po', function () {
    // Arrange
    // $this->withoutExceptionHandling();
    $user = $this->getAdmin();
    $stocks = FabricReceiving::factory()
        ->hasItems(random_int(1, 4))
        ->create(['status' => 'Close']);
    $stock = $stocks->first();

    $items = FabricReceivingItem::query()->whereIn('fabric_receiving_id', [$stock->id])->get();

    // Action
    $response = $this->actingAs($user)->post(
        route('purchases.pos.store'),
        [
            'stock' => [
                [
                    'id' => $stock->id,
                    'supplier_id' => $stock->supplier_id,
                    'bilti_no' => $stock->bilti_no,
                    'invoice_no' => $stock->invoice_no,
                    'lot_no' => $stock->lot_no,
                ],
            ],

        ]
    );
    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);
    $this->assertDatabaseHas(Purchase::class, [
        'stock_id' => $stock->id,
        'invoice_no' => $stock->invoice_no,
        'lot_no' => $stock->lot_no,
        'bilti_no' => $stock->bilti_no,
        'supplier_id' => $stock->supplier_id,
    ]);
    foreach ($items as $item) {
        $this->assertDatabaseHas(PurchaseItem::class, [
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'size' => $item->size,
            'qty' => $item->qty,
            'price' => '0',
        ]);
    }
});

test('create voucher from receiving', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()
        ->has(FabricReceivingItem::factory()->thaan()->count(3), 'items')
        ->create(['status' => 'Close'])
        ->first();
    $items = FabricReceivingItem::query()->where('fabric_receiving_id', $stock->id)->get();
    // Action
    $response = $this->actingAs($user)->post(
        route('purchases.pos.store'),
        [
            'stock' => [
                [
                    'id' => $stock->id,
                    'supplier_id' => $stock->supplier_id,
                    'bilti_no' => $stock->bilti_no,
                    'invoice_no' => $stock->invoice_no,
                ],
            ],
        ]
    );
    // Assert
    $response->assertSessionHasNoErrors()
        ->assertFound();

    $this->assertDatabaseHas(Purchase::class, [
        'stock_id' => $stock->id,
        'invoice_no' => $stock->invoice_no,
        'bilti_no' => $stock->bilti_no,
        'supplier_id' => $stock->supplier_id,
        'status' => StatusText::Open,
    ]);

    foreach ($items as $item) {
        $this->assertDatabaseHas(PurchaseItem::class, [
            'product_id' => $item->product_id,
            'unit' => $item->unit,
            'size' => $item->size,
            'qty' => $item->qty,
            'price' => '0',
        ]);
    }
});

test('create voucher from receiving duplicate not allowed', function () {
    $user = $this->getAdmin();

    $stock = FabricReceiving::factory()
        ->count(1)
        ->has(FabricReceivingItem::factory()->thaan()->count(3), 'items')
        ->create(['status' => 'Close'])
        ->first();
    Purchase::factory()->create(['stock_id' => $stock->id]);

    // Action
    $response = $this->actingAs($user)->post(
        route('purchases.pos.store'),
        [
            'stock' => [
                [
                    'id' => $stock->id,
                    'supplier_id' => $stock->supplier_id,
                    'bilti_no' => $stock->bilti_no,
                    'invoice_no' => $stock->invoice_no,
                ],
            ],
        ]
    );
    // Assert
    $response->assertRedirect();
    $response->assertSessionHasErrors();
});

test('update purchase voucher', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.update');
    $stock = Purchase::factory()->has(PurchaseItem::factory()->count(3), 'items')
        ->create();
    $body = [
        'bill_no' => 'V-100',
    ];

    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.pos.update', $stock->id), $body);

    // Assert
    $response->assertRedirect(route('purchases.pos.index'));
    $this->assertDatabaseHas(Purchase::class, $body);
});

test('confirm purchase voucher', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.update');
    $stock = FabricReceiving::factory()->create(['invoiced' => false]);
    $receipt = Purchase::factory()->has(PurchaseItem::factory()->count(3), 'items')
        ->create(['stock_ids' => $stock->id, 'id' => $stock->id]);
    $body = [
        'bill_no' => 'V-100',
        'status' => StatusText::Close->value,
    ];
    partialMock(UpdateStockPrice::class)
        ->expects('handle')
        ->withArgs(fn ($r) => $r->is($receipt));
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.pos.update', $receipt->id), $body);

    // Assert
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.pos.index'));
    $receipt->refresh();
    $this->assertDatabaseHas(FabricReceiving::class, ['id' => $receipt->id, 'invoiced' => true]);

    $response->assertRedirect(route('purchases.pos.index'));
    $this->assertDatabaseHas(Purchase::class, [
        'id' => $receipt->id,
        'status' => StatusText::Close,
        'transaction_date' => today(),
    ]);
    $this->assertDatabaseHas(Log::class, [
        'loggable_type' => $receipt->getMorphClass(),
        'loggable_id' => $receipt->id,
        'log' => json_encode(['action' => 'Receipt Confirmed', 'user' => ['id' => $user->id, 'name' => $user->name]]),
    ]);
    $this->assertDatabaseHas(Journal::class, [
        'resource_id' => $receipt->id,
        'resource_type' => $receipt->getMorphClass(),
        'detail' => $receipt->journalDetail(),
        'head' => JournalHead::Purchases->value,
        'posted_at' => today(),
    ]);
    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $receipt->supplier_id,
        'dr' => 0,
        'cr' => $receipt->total,
    ]);
});

test('purchase add box item', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->create();
    $product = Product::factory()->box()->create()->toArray();
    $product['product_id'] = $product['id'];
    $size = 4.5;
    $qty = 100;
    $body = [
        'voucher_no' => 'V-100',
        'product' => $product,
        'unit' => 'Box',
        'size' => $size,
        'qty' => $qty,
        'price' => 1400,
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.po.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    unset($body['product']);
    $total_qty = $qty * $size;
    $body['total_qty'] = $total_qty;
    $body['product_id'] = $product['id'];
    $this->assertDatabaseHas(PurchaseItem::class, $body);
    $this->assertDatabaseHas(Purchase::class, [
        'id' => $stock->id,
        'total' => 1400 * $qty,
    ]);
});

test('purchase update box item', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->has(PurchaseItem::factory()->count(3)->box(), 'items')
        ->create();
    $items = $stock->items;
    $product = Product::factory()->box()->create()->toArray();
    $product['product_id'] = $product['id'];
    $qty = 20;
    $price = 1400;
    $item = $items->first();
    $size = 4.5;
    $body = [
        'voucher_no' => 'V-100',
        'item_id' => $item->id,
        'product' => $product,
        'unit' => 'Box',
        'size' => $size,
        'qty' => $qty,
        'price' => $price,
    ];
    $total = $items->sum('total') - $item->total + ($qty * $price);

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.po.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    unset($body['item_id']);
    unset($body['product']);
    $body['total_qty'] = $qty * $size;
    $body['product_id'] = $product['id'];
    $body['total'] = $qty * $price;
    $this->assertDatabaseHas(PurchaseItem::class, $body);
    $this->assertDatabaseHas(Purchase::class, ['id' => $stock->id, 'total' => $total]);
});

test('purchase add thaan item', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->create();
    $product = Product::factory()->thaan()->create()->toArray();
    $product['product_id'] = $product['id'];
    $body = [
        'voucher_no' => 'V-100',
        'product' => $product,
        'unit' => 'Thaan',
        'qty' => 10,
        'total_qty' => 270,
        'price' => 350,
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.po.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    unset($body['product']);
    $body['product_id'] = $product['id'];
    $this->assertDatabaseHas(PurchaseItem::class, $body);
    $this->assertDatabaseHas(Purchase::class, [
        'id' => $stock->id,
        'total' => 350 * 270,
    ]);
});

test('purchase update thaan item', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->has(PurchaseItem::factory()->count(3)->box(), 'items')
        ->create();
    $items = $stock->items;
    $product = Product::factory()->thaan()->create()->toArray();
    $product['product_id'] = $product['id'];
    $qty = 20;
    $price = 350;
    $total_qty = 20 * 27;
    $item = $items->first();
    $body = [
        'voucher_no' => 'V-100',
        'item_id' => $item->id,
        'product' => $product,
        'unit' => 'Thaan',
        'qty' => $qty,
        'total_qty' => $total_qty,
        'price' => $price,
    ];
    $total = $items->sum('total') - $item->total + ($total_qty * $price);

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.po.item', $stock->id), $body);

    // Assert
    $response->assertOk();
    unset($body['item_id']);
    unset($body['product']);
    $body['product_id'] = $product['id'];
    $this->assertDatabaseHas(PurchaseItem::class, $body);
    $this->assertDatabaseHas(Purchase::class, ['id' => $stock->id, 'total' => $total]);
});

test('purchase voucher item delete', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = Purchase::factory()->has(
        PurchaseItem::factory()->thaan()->count(3),
        'items'
    )->create();

    $item = $stock->items()->first();

    // Act
    $response = $this->actingAs($user)
        ->delete(route('ajax.po.item.destroy', $item->id));

    // Assert
    $response->assertOk();
    $this->assertDatabaseMissing(PurchaseItem::class, $item->toArray());
    $this->assertDatabaseCount(PurchaseItem::class, 2);
});

test('purchase voucher delete', function () {
    // Arrange
    $this->withoutExceptionHandling();
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.destroy');
    $stock = FabricReceiving::factory()->create(['invoiced' => true]);
    $receipt = Purchase::factory()->has(
        PurchaseItem::factory()->thaan()->count(3),
        'items'
    )->create(['stock_id' => $stock->id, 'stock_ids' => $stock->id]);

    // Act
    $response = $this->actingAs($user)
        ->delete(route('purchases.pos.destroy', $receipt->id));

    // Assert
    $response->assertSessionHasNoErrors()->assertRedirect();
    assertDatabaseHas(FabricReceiving::class,
        [
            'id' => $stock->id,
            'invoiced' => 0,
        ]
    );
    assertDatabaseCount(Purchase::class, 0);
    assertDatabaseCount(PurchaseItem::class, 0);
    assertDatabaseHas(Log::class, [
        'loggable_id' => $receipt->id,
        'loggable_type' => $receipt->getMorphClass(),
    ]);
});

test('purchase voucher preview', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.show');
    $stock = Purchase::factory()
        ->has(
            PurchaseItem::factory()->box()->count(2),
            'items'
        )->create();

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.show', $stock->id));

    // Assert
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Purchases/PurchaseView')
        ->has('receipt')
        ->has('total_summary')
    );
});
