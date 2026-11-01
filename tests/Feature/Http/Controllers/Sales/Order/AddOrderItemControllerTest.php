<?php

use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('add item without stock at the location is saved with a warning',
    function (PackingType $unit, float $size, int $qty, float $price) {
        // Arrange
        $user = $this->getAdmin();
        $order = Order::factory()->create();
        $product = Product::factory()->thaan()->create();
        $product['product_id'] = $product->id;
        $post = [
            'product' => $product->toArray(),
            'unit' => $unit->name,
            'size' => $size,
            'price' => $price,
            'qty' => $qty,
            'discount' => 0,
            'commission' => '0',
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('ajax.so.item.add', $order->id), $post);

        // Assert
        $response->assertOk()->assertJsonCount(1, 'items');
        expect($response->json('stock_warnings.0'))->toContain($product->name, 'available at Shop');
        assertDatabaseCount(OrderItem::class, 1);
    })->with([
        'thaan' => [PackingType::Thaan, 21, 1, 350],
        'box' => [PackingType::Box, 5.5, 2, 1500],
        'suit' => [PackingType::Suit, 6.5, 3, 1500],
    ]);

test('add item warns only when the selected location is short of stock', function () {
    // Arrange
    $user = $this->getAdmin();
    $shopOrder = Order::factory()->create();
    $warehouseOrder = Order::factory()->create();
    $product = Product::factory()->create();
    $this->addInventory($product, PackingType::Box, 3, 5.5);
    Inventory::query()->update(['location' => StockLocation::WarehouseFSD]);
    $product['product_id'] = $product->id;
    $post = [
        'product' => $product->toArray(),
        'unit' => PackingType::Box->value,
        'size' => 5.5,
        'price' => 1500,
        'qty' => 2,
        'discount' => 0,
        'commission' => '0',
    ];

    // Act
    $fromShop = $this->actingAs($user)
        ->postJson(route('ajax.so.item.add', $shopOrder->id), [...$post, 'location' => StockLocation::Shop->value]);
    $fromWarehouse = $this->actingAs($user)
        ->postJson(route('ajax.so.item.add', $warehouseOrder->id), [...$post, 'location' => StockLocation::WarehouseFSD->value]);

    // Assert
    $fromShop->assertOk();
    expect($fromShop->json('stock_warnings.0'))->toContain('available at Shop');
    $fromWarehouse->assertOk()->assertJsonPath('stock_warnings', []);
    assertDatabaseCount(OrderItem::class, 2);
    assertDatabaseHas(OrderItem::class, [
        'order_id' => $warehouseOrder->id,
        'product_id' => $product->id,
        'location' => StockLocation::WarehouseFSD->value,
    ]);
});

test('add item warns when lines of the order together exceed the stock', function () {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    $this->addInventory($product, PackingType::Box, 3, 5.5);
    $product['product_id'] = $product->id;
    $post = [
        'product' => $product->toArray(),
        'unit' => PackingType::Box->value,
        'size' => 5.5,
        'price' => 1500,
        'qty' => 2,
        'discount' => 0,
        'commission' => '0',
    ];

    // Act
    $firstLine = $this->actingAs($user)->postJson(route('ajax.so.item.add', $order->id), $post);
    $secondLine = $this->actingAs($user)->postJson(route('ajax.so.item.add', $order->id), $post);

    // Assert
    $firstLine->assertOk()->assertJsonPath('stock_warnings', []);
    $secondLine->assertOk()->assertJsonCount(1, 'stock_warnings');
});

test('add thaan to order', function (PackingType $unit, float $size, int $qty, float $price, float $total) {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->create();
    // OrderItem::factory()->thaan()->create(['order_id' => $order->id]);
    $product = Product::factory()->thaan()->create();

    $this->addInventory($product, $unit, $qty, $size);
    $product['product_id'] = $product->id;
    $post = [
        'product' => $product->toArray(),
        'unit' => $unit->name,
        'size' => $size,
        'price' => $price,
        'qty' => $qty,
        'discount' => 0,
        'commission' => '0',
    ];

    // Act
    $response = $this->actingAs($user)
        ->postJson(route('ajax.so.item.add', $order->id), $post);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    assertDatabaseCount(OrderItem::class, 1);
    unset($post['product']);

    $post['product_id'] = $product->id;
    $post['total_qty'] = $qty * $size;
    assertDatabaseHas(OrderItem::class, $post);

    $total = [
        'id' => $order->id,
        'total' => $total,
        'total_qty' => $size * $qty,
        'net_total' => $total,
    ];
    assertDatabaseHas(Order::class, $total);
    /*assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'outbound_type' => $order->getMorphClass(),
        'outbound_id' => $order->id,
        'unit' => $unit,
        'size' => $size,
        'qty' => $qty,
    ]);*/
})->with([
    'thaan' => [PackingType::Thaan, 21, 1, 350, 350 * 21],
    'box' => [PackingType::Box, 5.5, 2, 1500, 2 * 1500],
    'suit' => [PackingType::Suit, 6.5, 3, 1500, 3 * 1500],
]);

test('add item to order with percentage discount', function () {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->percentageDiscount()->create();
    $product = Product::factory()->thaan()->create();
    $unit = PackingType::Thaan;
    $size = 21;
    $qty = 2;
    $price = 350;
    $discountPercent = 10;
    $this->addInventory($product, $unit, $qty, $size);
    $product['product_id'] = $product->id;
    $post = [
        'product' => $product->toArray(),
        'unit' => $unit->name,
        'size' => $size,
        'price' => $price,
        'qty' => $qty,
        'discount' => $discountPercent,
        'commission' => '0',
    ];

    // Act
    $response = $this->actingAs($user)
        ->postJson(route('ajax.so.item.add', $order->id), $post);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    assertDatabaseCount(OrderItem::class, 1);
    $totalAmount = $qty * $size * $price;
    assertDatabaseHas(OrderItem::class, [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_amount' => $totalAmount,
        'discount' => $totalAmount * ($discountPercent / 100),
    ]);
});

test('update order line item', function () {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->create();
    $item = OrderItem::factory()->suit()->create(['order_id' => $order->id]);
    $product = Product::factory()->suit()->create();
    $unit = PackingType::Box;
    $size = 5.5;
    $this->addInventory($product, $unit, 1, $size);
    $product['product_id'] = $product->id;

    $post = [
        'product' => $product->toArray(),
        'unit' => $unit->name,
        'size' => $size,
        'price' => 1500,
        'qty' => 1,
        'discount' => 0,
        'item_id' => $item->id,
        'commission' => '0',
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.so.item.add', $order->id), $post + ['oversold' => 1]);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    assertDatabaseCount(OrderItem::class, 1);
    unset($post['product']);
    unset($post['item_id']);

    $post['product_id'] = $product->id;
    assertDatabaseHas(OrderItem::class, $post);
    $total = [
        'id' => $order->id,
        'total' => 1500,
        'total_qty' => $size,
        'net_total' => 1500,
    ];
    assertDatabaseHas(Order::class, $total);
});

test('add items with commission per meter to order', function () {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->agent()->create();
    $product = Product::factory()->suit()->create();

    $product['product_id'] = $product->id;
    $unit = PackingType::Box;
    $size = 5.5;
    $post = [
        'product' => $product->toArray(),
        'unit' => $unit->name,
        'size' => $size,
        'price' => 1500,
        'qty' => 1,
        'discount' => 0,
        'commission' => 1,
    ];
    $this->addInventory($product, $unit, 1, $size);

    // Act
    $response = $this->actingAs($user)
        ->postJson(route('ajax.so.item.add', $order->id),
            $post); // allow oversold to disabled check

    // Assert
    $response->assertJsonMissingValidationErrors()
        ->assertOk();
    assertDatabaseCount(OrderItem::class, 1);
    unset($post['product']);
    $post['total_commission'] = round($size, 2);
    $post['product_id'] = $product->id;
    assertDatabaseHas(OrderItem::class, $post);
    $total = [
        'id' => $order->id,
        'total' => 1500,
        'total_qty' => $size,
        'commission' => 5,
        'net_total' => 1500,
    ];
    assertDatabaseHas(Order::class, $total);
});

test('add items with commission percentage to order', function () {
    // Arrange
    $user = $this->getAdmin();
    $order = Order::factory()->agent()->create();
    $product = Product::factory()->suit()->create();

    $product['product_id'] = $product->id;
    $price = 1500;
    $size = 5.5;
    $qty = 1;
    $unit = PackingType::Box;
    $this->addInventory($product, $unit, $qty, $size);
    $post = [
        'product' => $product->toArray(),
        'unit' => $unit->name,
        'size' => $size,
        'price' => $price,
        'qty' => $qty,
        'discount' => 0,
        'commission' => '2%',
    ];

    // Act
    $response = $this->actingAs($user)
        ->post(route('ajax.so.item.add', $order->id),
            $post + ['oversold' => 1]); // allow oversold to disabled check

    // Assert
    $response->assertJsonMissingValidationErrors()->assertOk();
    assertDatabaseCount(OrderItem::class, $qty);
    unset($post['product']);
    $commission_rate = 0.02; // 2%
    $post['total_commission'] = round($price * $commission_rate, 2);
    $post['product_id'] = $product->id;
    assertDatabaseHas(OrderItem::class, $post);
    $total = [
        'id' => $order->id,
        'total' => $qty * $price,
        'total_qty' => $size,
        'commission' => (int) ($price * $commission_rate),
        'net_total' => $qty * $price,
    ];
    assertDatabaseHas(Order::class, $total);
});
