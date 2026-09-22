<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\Return\ConfirmReturn;
use App\Enums\JournalHead;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

use function Pest\Laravel\partialMock;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('add purchase return form loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $product = Product::factory()->box()->create();
    Purchase::factory()->create();
    PurchaseItem::factory()->create(['product_id' => $product->id, 'price' => 100]);
    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Returns/ReturnFormNew')
        ->has('suppliers')
    );
});

test('edit purchase return form loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.update');
    $product = Product::factory()->box()->create();
    Purchase::factory()->create();
    PurchaseItem::factory()->create(['product_id' => $product->id, 'price' => 1100]);

    $por = PurchaseReturn::factory()
        ->hasItems(2)
        ->create([
            'info' => ['remarks' => 'this testing'],
        ]);
    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.edit', $por->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Returns/ReturnForm')
        ->has('por')
        ->has('por.supplier')
        ->has('por.items_with_product')
        ->has('products')
    );
});

test('save purchase return ', function () {
    // Arrange
    $user = $this->getAdmin();

    $supplier = Account::factory()->supplier()->create();

    $body = [
        'supplier' => [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
        ],
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('purchases.por.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors()
        ->assertOk();

    $this->assertDatabaseHas(PurchaseReturn::class, [
        'supplier_id' => $supplier->id,
        'total_amount' => 0,
        'total_qty' => 0,
        'status' => ReturnStatus::Open->value,
    ]);

    $this->assertDatabaseCount(Inventory::class, 0);
    $this->assertDatabaseCount(JournalDetail::class, 0);
});

test('update purchase return', function (ReturnStatus $status) {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.update');
    $thaan = Product::factory()->thaan()->create();
    $thaan['product_id'] = $thaan->id;
    $box = Product::factory()->box()->create();
    $box['product_id'] = $box->id;
    $supplier = Account::factory()->supplier()->create();
    $supplier['supplier_id'] = $supplier->id;
    $info = ['remarks' => 'Not sold'];
    /** @var PurchaseReturn $por */
    $por = PurchaseReturn::factory()
        ->has(PurchaseReturnItem::factory()
            ->recycle([$thaan, $box]),
            'items')
        ->create(['info' => $info]);
    $totalAmount = $por->items->sum('total_amount');
    $totalQty = $por->items->sum('total_qty');
    $discount = 100;
    $expenses = 500;
    $body = [
        'bilti_no' => 'Bilti-100',
        'bill_no' => 'Bill-100',
        'discount' => $discount,
        'expenses' => $expenses,
        'info' => $info,
        'status' => $status->value,
    ];
    if ($status === ReturnStatus::Closed) {
        partialMock(ConfirmReturn::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')->once();
        });
    }
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.por.update', $por->id), $body);

    // Assert
    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.por.index'));

    $this->assertDatabaseHas(PurchaseReturn::class, [
        'info' => json_encode($info),
        'bilti_no' => $body['bilti_no'],
        'bill_no' => $body['bill_no'],
        'discount' => $discount,
        'expenses' => $expenses,
        'status' => $status,
        'total_qty' => $totalQty,
        'total_amount' => $totalAmount + $expenses - $discount,
    ]);
    if ($status === ReturnStatus::Open) {
        $this->assertDatabaseCount(Inventory::class, 0);
        $this->assertDatabaseCount(Journal::class, 0);
        $this->assertDatabaseCount(JournalDetail::class, 0);
    }
})->with(ReturnStatus::cases());

test('closing a purchase return posts a real ledger entry with a transaction date', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.update');
    $supplier = Account::factory()->supplier()->create();
    $por = PurchaseReturn::factory()->create([
        'supplier_id' => $supplier->id,
        'info' => ['remarks' => 'Not sold'],
        'transaction_date' => null,
    ]);
    $body = [
        'bilti_no' => 'Bilti-100',
        'bill_no' => 'Bill-100',
        'discount' => 0,
        'expenses' => 0,
        'info' => ['remarks' => 'Not sold'],
        'status' => ReturnStatus::Closed->value,
    ];

    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.por.update', $por->id), $body);

    // Assert
    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.por.index'));

    $por->refresh();
    expect($por->status)->toBe(ReturnStatus::Closed)
        ->and($por->transaction_date)->not->toBeNull();

    $this->assertDatabaseHas(Journal::class, [
        'resource_id' => $por->id,
        'resource_type' => $por->getMorphClass(),
        'detail' => $por->journalDetail(),
        'head' => JournalHead::Purchases->value,
        'posted_at' => today(),
    ]);
    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $supplier->id,
        'dr' => 0,
        'cr' => $por->total,
    ]);
});
