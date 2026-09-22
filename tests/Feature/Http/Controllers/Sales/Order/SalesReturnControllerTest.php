<?php

use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentMode;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertModelMissing;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('sale returns index page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    SalesReturn::factory()->count(2)->create();

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Returns/ReturnIndex')
        ->has('rows')
        ->has('rows.data', 2)
        ->has('filters')
        ->has('canAdd')
        ->has('canView')
    );
});

test('sale return view can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $return = SalesReturn::factory()->closed()
        ->has(SalesReturnItem::factory()->count(2), 'returnItems')
        ->create();

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.show', $return->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Returns/ReturnView')
        ->has('total_summary')
        ->has('transaction_date')
        ->has('balance')
        ->has('so_return')
        ->has('net_balance')
    );
});

test('sale return edit page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $return = SalesReturn::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.edit', $return->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Returns/ReturnForm')
        ->has('return')
        ->has('file_info')
        ->has('products')
    );
});

test('sale return can be deleted', function () {
    // Arrange
    $user = $this->getAdmin();
    $return = SalesReturn::factory()->closed()->create();
    Inventory::factory()->withPurchase()->create([
        'stockable_id' => $return->id,
        'stockable_type' => $return->getMorphClass(),
    ]);
    Journal::factory()->create([
        'resource_type' => $return->getMorphClass(),
        'resource_id' => $return->id,
    ]);

    // Act
    $response = $this->actingAs($user)->delete(route('sales.returns.destroy', $return->id));

    // Assert
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.returns.index'));
    assertModelMissing($return);
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseCount(Journal::class, 0);
});

test('sale returns form loaded', function () {
    // arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    Account::factory()->customer()->create();
    // action
    $response = $this->get(route('sales.returns.create'));
    // assert
    $response->assertOk();

});
test('store sale return with agent commission', function () {
    // Arrange
    $user = $this->getAdmin();
    $agent = Account::factory()->agent()->create(['name' => 'Cash']);
    $brands = Brand::factory()->count(2)->create();
    $agent_rate = [];
    foreach ($brands as $brand) {
        $agent_rate[] = ['brand_'.$brand->id => 1];
    }
    $discount = 0;
    $customer = Account::factory()->customer()->create(
        ['agent_id' => $agent->id, 'discount' => $discount, 'commission_rate' => $agent_rate]
    );
    $customer['customer_id'] = $customer->id;
    $data = [
        'customer' => $customer->toArray(),
        'customer_id' => $customer->id,
    ];
    // Act
    $response = $this->actingAs($user)->post(route('sales.returns.store'), $data);

    // Assert
    $response->assertSessionDoesntHaveErrors()->assertRedirect();
    $this->assertDatabaseCount(SalesReturn::class, 1);
    $this->assertDatabaseHas(
        SalesReturn::class,
        [
            'customer_id' => $customer->id,
            'agent_id' => $customer->agent_id,
            'agent_rate' => json_encode($customer->commission_rate),
        ]
    );
});
test('update cash sale return', function () {
    // arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    $cashAccount = $this->creatCashAccount();
    $agent = Account::factory()->agent()->create();
    $customer = Account::factory()->customer()->create(['agent_id' => $agent->id]);
    $customer['customer_id'] = $customer->id;
    $product = Product::factory()->create();
    $total_amount = 100;
    $total_commission = 3;
    $data = [
        'customer' => $customer,
        'order_no' => 'ord-1001',
        'payment_mode' => PaymentMode::Cash->value,
        'info' => ['remarks' => 'testing'],
        'expenses' => 10,
        'status' => OrderStatus::Close->value,
        'discount' => 5,
        'items' => [
            [
                'total_qty' => 1,
                'rate' => 100,
                'qty' => 1,
                'size' => 1,
                'unit' => PackingType::Thaan->name,
                'total_commission' => $total_commission,
                'commission' => 3,
                'total_amount' => $total_amount,
                'name' => $product->name,
                'product' => [
                    'product_id' => $product->id,
                ],
            ],
        ],
    ];
    $return = SalesReturn::factory()->create([
        'sr' => 1,
        'invoice_no' => 'RINV-001',
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'order_no' => 100,
        'agent_id' => $agent->id,
    ]);

    // act
    $response = $this->put(route('sales.returns.update', $return->id), $data);
    // assert
    $net_amount = $total_amount - 5 + 10;
    $response->assertSessionDoesntHaveErrors()->assertRedirect();
    $this->assertDatabaseHas(SalesReturn::class, [
        'customer_id' => $customer->id,
        'total_qty' => 1,
        'amount' => $total_amount,
        'total_amount' => $net_amount,
        'commission' => $total_commission,
        'status' => ReturnStatus::Closed->value,
    ]);
    $this->assertDatabaseCount(Inventory::class, 1);
    $this->assertDatabaseCount(Journal::class, 1);
    $this->assertDatabaseCount(JournalDetail::class, 4);
    $this->verifyJournalDetail($customer->id, $net_amount, 0);
    $this->verifyJournalDetail($customer->id, 0, $net_amount);
    $this->verifyJournalDetail($cashAccount->id, $net_amount, 0);
    $this->verifyJournalDetail($customer->agent_id, 0, $total_commission);

});
test('on update delete existing journal for sales return', function () {
    // arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    $cashAccount = $this->creatCashAccount();
    $customer = Account::factory()->customer()->create();
    $customer['customer_id'] = $customer->id;
    $product = Product::factory()->create();
    $total_amount = 100;
    $total_commission = 3;
    $data = [
        'customer' => $customer,
        'order_no' => 'ord-1001',
        'status' => ReturnStatus::Closed->value,
        'payment_mode' => PaymentMode::Cash->value,
        'info' => ['remarks' => 'testing'],
        'discount' => 10,
        'expenses' => 15,
        'items' => [
            [
                'total_qty' => 1,
                'rate' => 100,
                'qty' => 1,
                'size' => 1,
                'unit' => PackingType::Thaan->name,
                'total_commission' => $total_commission,
                'commission' => 3,
                'total_amount' => $total_amount,
                'name' => $product->name,
                'product' => [
                    'product_id' => $product->id,
                ],
            ],
        ],
    ];
    $return = SalesReturn::factory()->create([
        'sr' => 1,
        'invoice_no' => 'RINV-001',
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'order_no' => 100,
    ]);

    // act
    $response = $this->put(route('sales.returns.update', $return->id), $data);
    // assert
    $net_amount = $total_amount - 10 + 15;
    $response->assertSessionDoesntHaveErrors()->assertRedirect();
    $this->assertDatabaseHas(SalesReturn::class, [
        'customer_id' => $customer->id,
        'total_qty' => 1,
        'amount' => $total_amount,
        'total_amount' => $net_amount,
    ]);
    $this->assertDatabaseCount(Journal::class, 1);
    $this->assertDatabaseCount(JournalDetail::class, 3);
    $this->verifyJournalDetail($customer->id, $net_amount, 0);
    $this->verifyJournalDetail($customer->id, 0, $net_amount);
    $this->verifyJournalDetail($cashAccount->id, $net_amount, 0);

});
test('update sale credit return', function () {
    // arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    $customer = Account::factory()->customer()->create();
    $balance = fake()->randomNumber(3);
    JournalDetail::factory()
        ->debit($balance)
        ->create(['account_id' => $customer->id]);
    $customer['customer_id'] = $customer->id;
    $product = Product::factory()->create();
    $total_commission = 0;
    $total_amount = 200;
    $data = [
        'customer' => $customer,
        'order_no' => 'ord-1001',
        'discount' => 0,
        'expenses' => 0,
        'status' => ReturnStatus::Closed->value,
        'payment_mode' => PaymentMode::Credit->value,
        'info' => ['remarks' => 'testing'],
        'items' => [
            [
                'total_qty' => 1,
                'rate' => 100,
                'qty' => 1,
                'size' => 1,
                'unit' => PackingType::Thaan->name,
                'total_commission' => $total_commission,
                'commission' => 3,
                'total_amount' => $total_amount,
                'name' => $product->name,
                'product' => [
                    'product_id' => $product->id,
                ],
            ],
        ],
    ];
    $return = SalesReturn::factory()->create([
        'sr' => 1,
        'invoice_no' => 'RINV-001',
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'order_no' => 100,
    ]);
    // act
    $response = $this->put(route('sales.returns.update', $return->id), $data);
    // assert
    $response->assertSessionDoesntHaveErrors()->assertRedirect();
    $this->assertDatabaseHas(SalesReturn::class, [
        'customer_id' => $customer->id,
        'total_qty' => 1,
        'status' => ReturnStatus::Closed->value,
        'balance' => $balance,
        'total_amount' => $total_amount,
    ]);
    $this->assertDatabaseCount(Journal::class, 2);
    $this->assertDatabaseCount(JournalDetail::class, 2);
    $this->verifyJournalDetail($customer->id, $total_amount, 0);

});
