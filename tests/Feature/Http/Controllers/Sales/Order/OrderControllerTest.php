<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 *
 * @see          OrderController
 */

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Enums\DirectoryType;
use App\Enums\DiscountType;
use App\Enums\JournalHead;
use App\Enums\OrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentMode;
use App\Enums\PurchaseType;
use App\Exceptions\UnableToAllocateStockException;
use App\Facades\Permission;
use App\Http\Controllers\Sales\Order\OrderController;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Catalog\Brand;
use App\Models\File;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
    // $this->fakeHavePermission();
});
it('create sale order', function (DiscountType $discountType) {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, ['sales.orders.store', 'sales.orders.edit']);
    $agent = Account::factory()->agent()->create();
    $discount = 3;
    $customer = Account::factory()->customer($discount, $discountType)->create(['agent_id' => $agent->id]);
    $customer['customer_id'] = $customer->id;
    $data = [
        'customer' => $customer->toArray(),
        'customer_id' => $customer->id,
    ];
    // Action
    $response = $this->post(route('sales.orders.store'), $data);

    // Assert
    $response->assertSessionHasNoErrors()
        ->assertRedirect();
    $this->assertDatabaseCount(Order::class, 1);
    $this->assertDatabaseHas(
        Order::class,
        [
            'customer_id' => $customer->id,
            'agent_id' => $customer->agent_id,
            'discount_rate' => $customer->discount,
            'discount_type' => $customer->discount_type->value,
        ]
    );
})->with(DiscountType::cases());

test('create sale order with cash customer', function () {
    // Arrange

    $this->attachPermissions($this->user, ['sales.orders.store', 'sales.orders.edit']);
    $agent = Account::factory()->agent()->create();
    $discount = 0;
    $customer = Account::factory()->customer()->create(['agent_id' => $agent->id, 'discount' => $discount]);
    $customer['customer_id'] = $customer->id;
    $data = [
        'customer' => $customer->toArray(),
        'customer_id' => $customer->id,
    ];
    // Act
    $response = $this->post(route('sales.orders.store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $this->assertDatabaseCount(Order::class, 1);
    assertDatabaseHas(Order::class, [
        'customer_id' => $customer->id,
        'agent_id' => $customer->agent_id,
        'discount_rate' => $customer->discount,
    ]);
});

test('create sale order with commission customer', function () {
    // Arrange
    $this->attachPermissions($this->user, ['sales.orders.store', 'sales.orders.edit']);
    $agent = Account::factory()->agent()->create();
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
    $response = $this->post(route('sales.orders.store'), $data);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseCount(Order::class, 1);
    assertDatabaseHas(
        Order::class,
        [
            'customer_id' => $customer->id,
            'agent_id' => $customer->agent_id,
            'discount_rate' => $customer->discount,
            'agent_rate' => json_encode($customer->commission_rate),
        ]
    );

});

test('sale order view test', function () {
    // Arrange
    $this->attachPermissions($this->user, 'sales.orders.index');
    Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->count(2)->create();
    // Act
    $response = $this->get(route('sales.orders.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderIndex')
        ->has('rows')
        ->has('rows.data', 2)
        ->has('filters')
    );
});

test('sale order view filter invoice no test', function () {
    // Arrange
    $this->attachPermissions($this->user, 'sales.orders.index');
    $inv_no = 'INV-10001';
    Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create(['invoice_no' => $inv_no]);
    Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->count(5)
        ->create();
    // Act
    $response = $this
        ->get(route('sales.orders.index', ['invoice_no' => $inv_no]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderIndex')
        ->has('rows')
        ->has('rows.data', 1)
        ->has('filters')
    );
});

test('sale order view filter closed status test', function () {

    // Arrange
    $this->attachPermissions($this->user, 'sales.orders.index');
    $status = OrderStatus::Close->value;
    Order::factory()
        ->closed()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();
    Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->count(5)
        ->create();
    // Act
    $response = $this
        ->get(route('sales.orders.index', ['status' => $status]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderIndex')
        ->has('rows')
        ->has('rows.data', 1)
        ->has('filters')
    );

});

test('sale order view filter customer name test', function () {
    // Arrange
    $this->attachPermissions($this->user, 'sales.orders.index');
    $name = fake()->name();
    $customer = Account::factory()->customer()->create(['name' => $name]);
    Order::factory()
        ->closed()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create(['customer_id' => $customer->id]);
    Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->count(5)
        ->create();
    // Act
    $response = $this
        ->get(route('sales.orders.index', ['customer_name' => $name]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderIndex')
        ->has('rows')
        ->has('rows.data', 1)
        ->has('filters')
    );
});

test('order view ok', function () {
    // Arrange

    $this->attachPermissions($this->user, 'sales.orders.show');
    $balance = fake()->numerify();
    $order = Order::factory()->closed()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create(['balance' => $balance]);

    // Act
    $response = $this->get(route('sales.orders.show', $order->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderView')
        ->has('order')
        ->where('balance', (int) $balance)
        ->where('net_balance', (int) ((float) $balance + (float) $order->net_total))
        ->has('total_summary')
        ->has('order.customer')
        ->has('order.items')
    );
});

test('gate pass not allowed for open sale order', function () {
    // Arrange
    Permission::fake(['sales.orders.gate-pass' => true]);

    $order = Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();

    // Act
    $response = $this->get(route('sales.orders.gate-pass', $order->id));

    // Assert
    $response->assertRedirect(route('sales.orders.edit', $order->id));
});

test('gate pass allowed for closed sale order', function () {
    // Arrange
    $this->withoutExceptionHandling();
    $this->attachPermissions($this->user, 'sales.orders.gate-pass');
    $order = Order::factory()->closed()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();

    // Act
    $response = $this->get(route('sales.orders.gate-pass', $order->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderGatePass')
        ->has('order')
        ->has('order.customer')
        ->has('order.items')
    );
});

test('update sale order', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();
    $body = [
        'payment_mode' => PaymentMode::Cash->value,
        'paid' => 1,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'purchase_type' => PurchaseType::InPerson->value,
        'status' => OrderStatus::Open->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $body);

    // Assert
    unset($body['customer']);
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.orders.index'));
    $body['id'] = $order->id;
    assertDatabaseHas(Order::class, $body);
});

test('update sale order discount on total', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();
    $body = [
        'payment_mode' => PaymentMode::Cash->value,
        'purchase_type' => PurchaseType::InPerson->value,
        'discount_on_total' => 100,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'paid' => 1,
        'status' => OrderStatus::Open->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $body);

    // Assert
    unset($body['customer']);
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.orders.index'));
    $body['id'] = $order->id;
    assertDatabaseHas(Order::class, $body);
});

test('update with expenses sale order', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();
    $body = [
        'payment_mode' => PaymentMode::Cash->value,
        'purchase_type' => PurchaseType::InPerson->value,
        'paid' => 1,
        'expenses' => 100,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'expenses_detail' => 'shipping expenses',
        'status' => OrderStatus::Open->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $body);

    // Assert
    unset($body['customer']);
    $response->assertSessionDoesntHaveErrors();
    $order->refresh();
    $response->assertRedirect(route('sales.orders.index'));
    $body['id'] = $order->id;
    $body['total'] = $order->total;
    $body['net_total'] = $order->total + 100;
    assertDatabaseHas(Order::class, $body);
});

test('update sale order payment mode auto paid', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3), 'items')
        ->create();
    $body = [
        'payment_mode' => PaymentMode::Cash->value,
        'purchase_type' => PurchaseType::InPerson->value,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        // 'paid' => 0,
        'status' => OrderStatus::Open->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $body);

    // Assert
    unset($body['customer']);
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.orders.index'));
    $body['id'] = $order->id;
    $body['paid'] = 1;
    assertDatabaseHas(Order::class, $body);
});

test('order confirm', function (PaymentMode $mode, bool $expense) {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $this->withoutExceptionHandling();
    $cashAccount = $this->creatCashAccount();
    $expenseAccount = Account::factory()->expense()->create();

    /** @var Order $order */
    $order = Order::factory()
        ->has(OrderItem::factory(3)
            ->commission(1)
            ->sequence(
                ['unit' => PackingType::Thaan],
                ['unit' => PackingType::Suit],
                ['unit' => PackingType::Box]
            ), 'items')
        ->agent_per_meter()
        ->create();
    if ($expense) {
        $agent = $order->agent;
        $agent->expense_account = $expenseAccount->id;
        $agent->update();
    }

    $items = $order->items;

    $product_1 = $items->first();
    $product_2 = $items[1];
    $product_3 = $items->last();
    $this->addInventory($product_1->product, PackingType::Thaan, $product_1->qty, $product_1->size);
    $this->addInventory($product_2->product, PackingType::Suit, $product_2->qty, $product_2->size);
    $this->addInventory($product_3->product, PackingType::Box, $product_3->qty, $product_3->size);

    $orderPaid = OrderPaid::Paid;
    if ($mode === PaymentMode::Credit) {
        $orderPaid = OrderPaid::UnPaid;
    }
    $data = [
        'purchase_type' => PurchaseType::InPerson->value,
        'paid' => $orderPaid->value,
        'payment_mode' => $mode->value,
        'status' => OrderStatus::Close->value,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.orders.index'));
    assertDatabaseCount(Inventory::class, 3);
    unset($data['customer']);
    $data['id'] = $order->id;
    $data['customer_id'] = $order->customer_id;
    $data['balance'] = 0;

    assertDatabaseHas(Order::class, $data);
    $order->refresh();
    assertDatabaseHas(Log::class, [
        'loggable_type' => $order->getMorphClass(),
        'loggable_id' => $order->id,
        'log' => json_encode([
            'action' => 'SO confirmed by user', 'user' => ['id' => $this->user->id, 'name' => $this->user->name],
        ]),
    ]);
    assertDatabaseHas(Journal::class, [
        'resource_id' => $order->id,
        'resource_type' => $order->getMorphClass(),
        'detail' => $order->journalDetail(),
        'head' => JournalHead::Sales->value,
        'posted_at' => $order->transaction_date->toDateString(),
    ]);
    assertDatabaseHas(JournalDetail::class, [
        'account_id' => $order->customer_id,
        'cr' => 0,
        'dr' => $order->net_total,
    ]);
    if ($mode === PaymentMode::Cash) {
        assertDatabaseHas(JournalDetail::class, [
            'account_id' => $order->customer_id,
            'dr' => 0,
            'cr' => $order->net_total,
        ]);
        assertDatabaseHas(JournalDetail::class, [
            'account_id' => $cashAccount->id,
            'cr' => 0,
            'dr' => $order->net_total,
        ]);
    }
    if ($mode === PaymentMode::Credit) {
        assertDatabaseMissing(JournalDetail::class, [
            'account_id' => $cashAccount->id,
            'cr' => 0,
            'dr' => $order->net_total,
        ]);
    }
    if ($expense) {
        assertDatabaseHas(JournalDetail::class, [
            'account_id' => $expenseAccount->id,
            'cr' => 0,
            'dr' => $order->total_qty,
        ]);
        assertDatabaseHas(JournalDetail::class, [
            'account_id' => $order->agent_id,
            'dr' => 0,
            'cr' => $order->total_qty,
        ]);
    }
    foreach ($items as $item) {
        assertDatabaseHas(Inventory::class, [
            'product_id' => $item->product_id,
            'size' => $item->size,
            'unit' => $item->unit,
            'qty' => $item->qty,
            'meters' => $item->total_qty,
            'outbound_id' => $order->id,
            'outbound_type' => $order->getMorphClass(),
            'outbound_item_id' => $item->id,
        ]);
    }
})
    ->with([
        'Payment' => PaymentMode::Cash,
        'Credit' => PaymentMode::Credit,
    ])
    ->with([
        'hasExpense' => true,
        'NoExpense' => false,
    ]);
test('order confirm without payment', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $cashAccount = $this->creatCashAccount();
    /** @var Order $order */
    $order = Order::factory()
        ->hasItems(3)
        ->fromShop()->create();
    $balance = fake()->randomNumber(3);
    JournalDetail::factory()->create(['dr' => $balance, 'cr' => 0, 'account_id' => $order->customer_id]);
    $orderItems = $order->items;
    foreach ($orderItems as $orderItem) {
        $this->addInventory($orderItem->product, $orderItem->unit, $orderItem->qty, $orderItem->size);

    }

    $data = [
        'purchase_type' => PurchaseType::InPerson->value,
        'payment_mode' => PaymentMode::Credit->value,
        // 'paid' => OrderPaid::Paid->value,
        'status' => OrderStatus::Close->value,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.orders.index'));
    unset($data['customer']);
    $data['id'] = $order->id;
    $data['customer_id'] = $order->customer_id;
    $data['balance'] = $balance;
    $data['paid'] = OrderPaid::UnPaid->value;
    assertDatabaseHas(Order::class, $data);
    $order_info = $order->refresh();

    // verify sales ledger
    assertDatabaseMissing(JournalDetail::class, [
        'account_id' => $order_info->customer_id,
        'dr' => 0,
        'cr' => $order_info->net_total,
    ]);

    $this->verifyJournal(
        $order_info->id,
        $order_info->customer_id,
        $order_info->net_total,
        0,
        $order_info->journalDetail()
    );

    // verify inventory deduction
    foreach ($orderItems as $item) {
        assertDatabaseHas(Inventory::class, [
            'product_id' => $item->product_id,
            'size' => $item->size,
            'unit' => $item->unit,
            'qty' => $item->qty,
            'meters' => $item->total_qty,
            'outbound_id' => $order->id,
            'outbound_type' => $order->getMorphClass(),
            'outbound_item_id' => $item->id,
        ]);
    }
});

test('order confirm again but inventory stays', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $this->creatCashAccount();
    $order = Order::factory()->fromShop()->create();
    $product_1 = OrderItem::factory()->thaan()->create([
        'order_id' => $order->id, 'qty' => 3, 'size' => 25, 'total_qty' => 75,
    ]);
    $product_2 = OrderItem::factory()->suit()->create([
        'order_id' => $order->id, 'qty' => 2, 'size' => 5.5, 'total_qty' => 11,
    ]);
    $product_3 = OrderItem::factory()->thaan()->create([
        'order_id' => $order->id, 'qty' => 4, 'size' => 21, 'total_qty' => 84,
    ]);
    $this->addInventory($product_1->product, $product_1->unit, $product_1->qty, $product_1->size);
    $this->addInventory($product_2->product, $product_2->unit, $product_2->qty, $product_2->size);
    $this->addInventory($product_3->product, $product_3->unit, $product_3->qty, $product_3->size);
    $data = [
        'purchase_type' => PurchaseType::InPerson->value,
        'paid' => OrderPaid::Paid->value,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'payment_mode' => PaymentMode::Cash->value,
        'status' => OrderStatus::Close->value,
        'customer' => $order->customer,
    ];

    // Act
    $this->put(route('sales.orders.update', $order->id), $data);
    $response = $this->put(route('sales.orders.update', $order->id), $data);

    // Assert
    unset($data['customer']);
    $data['id'] = $order->id;
    $data['customer_id'] = $order->customer_id;
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.orders.index'));
    // re-confirming an already-closed order must not re-issue inventory
    $this->assertDatabaseCount(Inventory::class, 3);
    assertDatabaseHas(Order::class, $data);
    $order_info = Order::find($order->id);

    // verify sales ledger
    $this->verifyJournal($order_info->id, $order_info->customer_id, 0, $order_info->net_total,
        $order_info->journalDetail());

    $this->verifyJournalDetail($order_info->customer_id, $order_info->net_total, 0);

    // verify each product's stock was consumed exactly once, by this order
    foreach ([$product_1, $product_2, $product_3] as $product) {
        assertDatabaseHas(
            Inventory::class,
            [
                'product_id' => $product->product_id,
                'unit' => $product->unit,
                'outbound_id' => $order->id,
                'outbound_type' => Order::morphClass(),
                'outbound_item_id' => $product->id,
            ]
        );
    }
});

test('order confirm with commission', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $this->creatCashAccount();
    $expenseAccount = Account::factory()->expense()->create();
    $order = Order::factory()->agent_per_meter()->fromShop()->create();
    $agent = $order->agent;
    $agent->expense_account = $expenseAccount->id;
    $agent->update();
    $orderItem = OrderItem::factory()->commission(1)
        ->create(['order_id' => $order->id]);
    $this->addInventory($orderItem->product, $orderItem->unit, $orderItem->qty, $orderItem->size);
    resolve(UpdateOrderTotal::class)->handle($order);
    $data = [
        'purchase_type' => PurchaseType::InPerson->value,
        'payment_mode' => PaymentMode::Credit->value,
        'paid' => OrderPaid::Paid->value,
        'customer' => $order->customer,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'status' => OrderStatus::Close->value,
    ];
    $detail = [
        'invoice' => 'Inv No: '.$order->invoice_no,
        'customer' => $order->customer->name,
        // 'qty' => 'Qty: ' . $order->total_qty
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $data);

    // Assert
    unset($data['customer']);
    $data['id'] = $order->id;
    $data['customer_id'] = $order->customer_id;
    $data['paid'] = OrderPaid::UnPaid->value;
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.orders.index'));
    $order_info = Order::find($order->id);
    assertDatabaseHas(Order::class, $data);

    // verify sales commission per meter
    $this->verifyJournal($order_info->id, $order_info->agent_id, 0, $order_info->total_qty, implode(', ', $detail));
    $this->verifyJournalDetail($expenseAccount->id, 0, $order_info->total_qty);
});

test('order confirm fails when stock cannot be allocated', function () {
    // Arrange
    Permission::fake(['sales.orders.update' => true]);
    $this->creatCashAccount();
    $order = Order::factory()
        ->has(OrderItem::factory()->count(1), 'items')
        ->create();
    $this->mock(ConfirmOrder::class, function ($mock) {
        $mock->shouldReceive('handle')
            ->once()
            ->andThrow(UnableToAllocateStockException::unableToAllocate('Product', 10, 5));
    });
    $data = [
        'purchase_type' => PurchaseType::InPerson->value,
        'paid' => OrderPaid::Paid->value,
        'payment_mode' => PaymentMode::Cash->value,
        'status' => OrderStatus::Close->value,
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'customer' => $order->customer,
    ];

    // Act
    $response = $this->put(route('sales.orders.update', $order->id), $data);

    // Assert
    $response->assertSessionHasErrors('product');
});

test('sale order create page can be rendered', function () {
    // Arrange
    Permission::fake(['sales.orders.create' => true]);
    Account::factory()->customer()->count(2)->create();

    // Act
    $response = $this->get(route('sales.orders.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderFormNew')
        ->has('customers', 2)
    );
});

test('creating order for suspended customer is rejected', function () {
    // Arrange
    Permission::fake(['sales.orders.store' => true]);
    $customer = Account::factory()->customer()->create(['suspended' => true]);
    $customer['customer_id'] = $customer->id;
    $data = [
        'customer' => $customer->toArray(),
        'customer_id' => $customer->id,
    ];

    // Act
    $response = $this->post(route('sales.orders.store'), $data);

    // Assert
    $response->assertSessionHasErrors('customer');
    $response->assertRedirect();
    assertDatabaseCount(Order::class, 0);
});

test('sale order edit page can be rendered', function () {
    // Arrange
    Permission::fake(['sales.orders.*' => true]);
    $order = Order::factory()
        ->has(OrderItem::factory()->count(2), 'items')
        ->create();

    // Act
    $response = $this->get(route('sales.orders.edit', $order->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderForm')
        ->has('order')
        ->has('products')
        ->has('items')
        ->has('types')
        ->has('discountTypes')
    );
});

test('sale order edit is blocked for a closed order', function () {
    // Arrange
    // scoped fake so Gate::before's wildcard bypass doesn't skip the policy's status check
    Permission::fake(['sales.orders.edit' => true, 'sales.orders.update' => true]);
    $order = Order::factory()->closed()->create();

    // Act
    $response = $this->get(route('sales.orders.edit', $order->id));

    // Assert
    $response->assertRedirect(route('sales.orders.index'));
    $response->assertSessionHas('error');
});

test('order items ajax endpoint returns items', function () {
    // Arrange
    $order = Order::factory()
        ->has(OrderItem::factory()->count(2), 'items')
        ->create();

    // Act
    $response = $this->getJson(route('ajax.so.items', $order->id));

    // Assert
    $response->assertOk();
    $response->assertJsonCount(2, 'items');
});

test('bilti view is blocked for an open order', function () {
    // Arrange
    $order = Order::factory()->create();

    // Act
    $response = $this->get(route('sales.order.bilti', $order->id));

    // Assert
    $response->assertRedirect(route('sales.orders.index', $order->id));
    $response->assertSessionHas('error');
});

test('bilti view can be rendered for a closed order', function () {
    // Arrange
    $order = Order::factory()->closed()->create();

    // Act
    $response = $this->get(route('sales.order.bilti', $order->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Orders/OrderBiltiForm')
        ->has('order')
        ->has('transaction_date')
        ->has('attachments')
    );
});

test('bilti file can be uploaded', function () {
    // Arrange
    $order = Order::factory()->closed()->create();
    $file = File::factory()->create([
        'created_by' => $this->user->id,
        'directory' => DirectoryType::SalesBilties->value,
    ]);

    // Act
    $response = $this->post(route('sales.order.bilti.create', ['order' => $order->id, 'file' => $file->id]));

    // Assert
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.orders.index'));
    $file->refresh();
    expect($file->fileable_id)->toBe($order->id)
        ->and($file->fileable_type)->toBe($order->getMorphClass())
        ->and($order->refresh()->has_bilti)->toBeTrue();
});
