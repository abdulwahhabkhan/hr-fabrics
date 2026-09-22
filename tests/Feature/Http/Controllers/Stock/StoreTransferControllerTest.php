<?php

namespace Tests\Feature\Http\Controllers\Stock;

use App\Actions\Stock\StoreTransfers\ConfirmStoreTransfer;
use App\Enums\PackingType;
use App\Enums\PaymentMode;
use App\Enums\StoreTransferStatus;
use App\Enums\StoreTransferType;
use App\Facades\Permission;
use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\post;

beforeEach(function () {
    // $this->fakeHavePermission();
    Permission::fake(['stocks.store-transfers.*' => true]);
    $this->actingAs($this->getAdmin());
});

function addStoreTransferInventory(
    Product $product,
    string $unit,
    int $qty = 1,
    float $size = 5.5,
    float $cost = 0
): Inventory {
    return Inventory::factory()->withPurchase()->create([
        'product_id' => $product->id,
        'unit' => $unit,
        'qty' => $qty,
        'size' => $size,
        'meters' => $qty * $size,
        'cost' => $cost,
    ]);
}

test('store transfer index page can be rendered', function () {
    StoreTransfer::factory()->count(3)->create();

    $response = $this->get(route('stocks.store-transfers.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/StoreTransfers/StoreTransferIndex')
        ->has('rows.data', 3)
        ->has('filters')
    );
});

test('store transfer index page can filter by transfer_no', function () {
    StoreTransfer::factory()->create(['transfer_no' => 'ABC123']);
    StoreTransfer::factory()->create(['transfer_no' => 'XYZ999']);

    $response = $this->get(route('stocks.store-transfers.index', ['transfer_no' => 'ABC']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/StoreTransfers/StoreTransferIndex')
        ->has('rows.data', 1)
    );
});

test('store transfer create page can be rendered', function () {
    Account::factory()->storeType()->count(2)->create();

    $response = $this->get(route('stocks.store-transfers.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/StoreTransfers/StoreTransferFormNew')
        ->has('stores', 2)
    );
});

test('store transfer can be created', function () {
    $store = Account::factory()->storeType()->create();

    $data = [
        'account_id' => $store->id,
        'account' => ['account_id' => $store->id, 'account_name' => $store->name],
        'type' => StoreTransferType::Store->value,
    ];

    $response = post(route('stocks.store-transfers.store'), $data);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas(StoreTransfer::class, [
        'account_id' => $store->id,
        'type' => StoreTransferType::Store->value,
        'status' => StoreTransferStatus::Open->value,
    ]);
    $transfer = StoreTransfer::first();
    $response->assertRedirect(route('stocks.store-transfers.edit', $transfer->id));
});

test('store transfer can be updated with payment mode, notes, expenses and discount', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id, 'total_amount' => 1000]);
    $transfer->refresh();

    $data = [
        'account_id' => $store->id,
        'account' => ['account_id' => $store->id],
        'type' => StoreTransferType::Store->value,
        'payment_mode' => PaymentMode::Cash->value,
        'notes' => 'Handle with care',
        'expenses' => 50,
        'discount_on_total' => 20,
    ];

    $response = $this->put(route('stocks.store-transfers.update', $transfer->id), $data);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas(StoreTransfer::class, [
        'id' => $transfer->id,
        'payment_mode' => PaymentMode::Cash->value,
        'notes' => 'Handle with care',
        'expenses' => 50,
        'discount_on_total' => 20,
        'total' => 1000,
        'net_total' => 1000 + 50 - 20,
    ]);
});

test('store transfer edit page redirects when already confirmed', function () {
    $transfer = StoreTransfer::factory()->closed()->create();

    $response = $this->get(route('stocks.store-transfers.edit', $transfer->id));

    $response->assertRedirect(route('stocks.store-transfers.index'));
    $response->assertSessionHas('error');
});

test('store transfer item can be added via ajax when stock is available', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();
    addStoreTransferInventory($product, PackingType::Thaan->value, 1, 21);

    $post = [
        'product' => ['product_id' => $product->id, 'name' => $product->name],
        'unit' => PackingType::Thaan->value,
        'size' => 21,
        'price' => 110,
        'expense' => 5,
        'qty' => 1,
    ];

    $response = $this->post(route('stocks.store-transfers.item', $transfer->id), $post);

    $response->assertJsonMissingValidationErrors()->assertOk();
    $this->assertDatabaseCount(StoreTransferItem::class, 1);
    $this->assertDatabaseHas(StoreTransferItem::class, [
        'store_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit' => PackingType::Thaan->value,
        'total_qty' => 21,
        'total_amount' => 21 * (110 + 5),
    ]);
    $this->assertDatabaseHas(StoreTransfer::class, [
        'id' => $transfer->id,
        'total' => 21 * (110 + 5),
        'total_qty' => 21,
    ]);
});

test('store transfer item expense is charged per box when unit is box', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->box()->create();
    addStoreTransferInventory($product, PackingType::Box->value, 5, 5);

    $post = [
        'product' => ['product_id' => $product->id, 'name' => $product->name],
        'unit' => PackingType::Box->value,
        'size' => 5,
        'price' => 110,
        'expense' => 5,
        'qty' => 3,
    ];

    $response = $this->post(route('stocks.store-transfers.item', $transfer->id), $post);

    $response->assertJsonMissingValidationErrors()->assertOk();
    $this->assertDatabaseHas(StoreTransferItem::class, [
        'store_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit' => PackingType::Box->value,
        'total_qty' => 15,
        'total_amount' => 3 * 110 + 3 * 5,
    ]);
});

test('store transfer item is rejected when stock is insufficient and type is store', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();

    $post = [
        'product' => ['product_id' => $product->id, 'name' => $product->name],
        'unit' => PackingType::Thaan->value,
        'size' => 21,
        'price' => 110,
        'expense' => 5,
        'qty' => 1,
    ];

    $response = $this->postJson(route('stocks.store-transfers.item', $transfer->id), $post);

    $response->assertUnprocessable();
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});

test('store transfer item skips inventory check when type is return', function () {
    $transfer = StoreTransfer::factory()->returnType()->create();
    $product = Product::factory()->thaan()->create();

    $post = [
        'product' => ['product_id' => $product->id, 'name' => $product->name],
        'unit' => PackingType::Thaan->value,
        'size' => 21,
        'price' => 110,
        'expense' => 5,
        'qty' => 1,
    ];

    $response = $this->post(route('stocks.store-transfers.item', $transfer->id), $post);

    $response->assertJsonMissingValidationErrors()->assertOk();
    $this->assertDatabaseCount(StoreTransferItem::class, 1);
});

test('store transfer item can be deleted via ajax', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $item = StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);

    $response = $this->delete(route('stocks.store-transfers.item.destroy', $item->id));

    $response->assertOk();
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
    $this->assertDatabaseHas(StoreTransfer::class, ['id' => $transfer->id, 'total' => 0, 'total_qty' => 0]);
});

test('confirming a store transfer debits the store account', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);
    $transfer->refresh();

    $data = [
        'account_id' => $store->id,
        'account' => ['account_id' => $store->id],
        'type' => StoreTransferType::Store->value,
        'status' => StoreTransferStatus::Closed->value,
    ];

    $response = $this->put(route('stocks.store-transfers.update', $transfer->id), $data);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('stocks.store-transfers.index'));
    $transfer->refresh();
    expect($transfer->status)->toBe(StoreTransferStatus::Closed);

    $this->verifyJournal($transfer->id, $store->id, $transfer->total, 0, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, 0, $transfer->total);
});

test('confirming a store transfer with expenses and discount posts the net total to the ledger', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);
    $transfer->refresh();

    $data = [
        'account_id' => $store->id,
        'account' => ['account_id' => $store->id],
        'type' => StoreTransferType::Store->value,
        'expenses' => 100,
        'discount_on_total' => 30,
        'status' => StoreTransferStatus::Closed->value,
    ];

    $response = $this->put(route('stocks.store-transfers.update', $transfer->id), $data);

    $response->assertSessionHasNoErrors();
    $transfer->refresh();
    $netTotal = $transfer->total + 100 - 30;
    expect($transfer->net_total)->toBe($netTotal);

    $this->verifyJournal($transfer->id, $store->id, $netTotal, 0, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, 0, $netTotal);
});

test('confirming a return transfer credits the store account', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->returnType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);
    $transfer->refresh();

    $data = [
        'account_id' => $store->id,
        'account' => ['account_id' => $store->id],
        'type' => StoreTransferType::Return->value,
        'status' => StoreTransferStatus::Closed->value,
    ];

    $response = $this->put(route('stocks.store-transfers.update', $transfer->id), $data);

    $response->assertSessionHasNoErrors();
    $transfer->refresh();
    expect($transfer->status)->toBe(StoreTransferStatus::Closed);

    $this->verifyJournal($transfer->id, $store->id, 0, $transfer->total, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, $transfer->total, 0);
});

test('unlocking a store transfer closed today reopens it and clears the journal', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);
    $transfer->refresh();
    (new ConfirmStoreTransfer)->handle($transfer, auth()->user());
    $transfer->refresh();
    $journalId = $transfer->journal()->value('id');

    $response = $this->post(route('actions.store-transfer.open', $transfer->id));

    $response->assertRedirect(route('stocks.store-transfers.edit', $transfer->id));
    $this->assertDatabaseHas(StoreTransfer::class, [
        'id' => $transfer->id,
        'status' => StoreTransferStatus::Open->value,
    ]);
    $this->assertDatabaseMissing(Journal::class, ['id' => $journalId]);
});

test('store transfer stock lots can be loaded grouped by size and cost', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();
    addStoreTransferInventory($product, PackingType::Thaan->value, 3, 21, 110);
    addStoreTransferInventory($product, PackingType::Thaan->value, 2, 21, 110);
    addStoreTransferInventory($product, PackingType::Thaan->value, 4, 28, 0);

    $response = $this->getJson(route('stocks.store-transfers.stock', $transfer->id).'?'.http_build_query([
        'product_id' => $product->id,
        'unit' => PackingType::Thaan->value,
    ]));

    $response->assertOk();
    $rows = collect($response->json('rows'));
    expect($rows)->toHaveCount(2)
        ->and($rows->firstWhere('size', 21)['available_qty'])->toEqual(5)
        ->and($rows->firstWhere('size', 28)['available_qty'])->toEqual(4);
});

test('loaded stock can be saved as store transfer items and books matching inventory lots', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();
    $inventory = addStoreTransferInventory($product, PackingType::Thaan->value, 5, 21, 110);

    $post = [
        'expense' => 5,
        'rows' => [
            [
                'product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110,
                'qty' => 3,
            ],
        ],
    ];

    $response = $this->postJson(route('stocks.store-transfers.items.bulk', $transfer->id), $post);

    $response->assertOk();
    $this->assertDatabaseCount(StoreTransferItem::class, 1);
    $item = StoreTransferItem::first();
    expect((float) $item->qty)->toBe(3.0)
        ->and((float) $item->price)->toBe(110.0);

    $inventory->refresh();
    expect($inventory->qty)->toBe(2)
        ->and($inventory->outbound_id)->toBeNull();

    $booked = Inventory::query()
        ->where('outbound_type', StoreTransfer::morphClass())
        ->where('outbound_item_id', $item->id)
        ->first();
    expect($booked)->not->toBeNull()
        ->and($booked->qty)->toBe(3);
});

test('loaded stock save is rejected when requested quantity exceeds available stock', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();
    addStoreTransferInventory($product, PackingType::Thaan->value, 2, 21, 110);

    $post = [
        'expense' => 5,
        'rows' => [
            [
                'product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110,
                'qty' => 5,
            ],
        ],
    ];

    $response = $this->postJson(route('stocks.store-transfers.items.bulk', $transfer->id), $post);

    $response->assertUnprocessable();
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});

test('loaded stock bulk endpoint is rejected for return type transfers', function () {
    $transfer = StoreTransfer::factory()->returnType()->create();
    $product = Product::factory()->thaan()->create();

    $post = [
        'expense' => 5,
        'rows' => [
            [
                'product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110,
                'qty' => 1,
            ],
        ],
    ];

    $response = $this->postJson(route('stocks.store-transfers.items.bulk', $transfer->id), $post);

    $response->assertUnprocessable();
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
});

test('deleting a loaded stock item releases its booked inventory', function () {
    $transfer = StoreTransfer::factory()->storeType()->create();
    $product = Product::factory()->thaan()->create();
    addStoreTransferInventory($product, PackingType::Thaan->value, 5, 21, 110);

    $this->postJson(route('stocks.store-transfers.items.bulk', $transfer->id), [
        'expense' => 5,
        'rows' => [
            [
                'product_id' => $product->id, 'unit' => PackingType::Thaan->value, 'size' => 21, 'cost' => 110,
                'qty' => 3,
            ],
        ],
    ]);
    $item = StoreTransferItem::first();

    $response = $this->delete(route('stocks.store-transfers.item.destroy', $item->id));

    $response->assertOk();
    $this->assertDatabaseCount(StoreTransferItem::class, 0);
    $this->assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'qty' => 3,
        'outbound_id' => null,
        'outbound_type' => null,
        'outbound_item_id' => null,
    ]);
});

test('unlocking a store transfer not closed today is rejected', function () {
    PermissionFacade::fake(['*' => true, 'unlock' => false]);
    $transfer = StoreTransfer::factory()->storeType()->closed()->create(['updated_at' => now()->subDay()]);

    $response = $this->post(route('actions.store-transfer.open', $transfer->id));

    $response->assertForbidden();
    $this->assertDatabaseHas(StoreTransfer::class, [
        'id' => $transfer->id,
        'status' => StoreTransferStatus::Closed->value,
    ]);
});

test('store transfer index exposes ledger and inventory row actions for a closed transfer', function () {
    StoreTransfer::factory()->closed()->create();
    StoreTransfer::factory()->create();

    $response = $this->get(route('stocks.store-transfers.index'));

    $rows = collect($response->viewData('page')['props']['rows']['data'])->keyBy('status');
    expect($rows[StoreTransferStatus::Closed->value]['can_ledger'])->toBeTrue()
        ->and($rows[StoreTransferStatus::Closed->value]['can_inventory'])->toBeTrue()
        ->and($rows[StoreTransferStatus::Open->value]['can_ledger'])->toBeFalse()
        ->and($rows[StoreTransferStatus::Open->value]['can_inventory'])->toBeFalse();
});
