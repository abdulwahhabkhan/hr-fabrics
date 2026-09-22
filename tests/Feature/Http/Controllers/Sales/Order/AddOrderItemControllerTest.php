<?php

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('add item failed due to no inventory',
    function (PackingType $unit, float $size, int $qty, float $price, float $total) {
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
        $response->assertJsonValidationErrorFor('qty')->assertUnprocessable();
        assertDatabaseCount(OrderItem::class, 0);

    })->with([
        'thaan' => [PackingType::Thaan, 21, 1, 350, 350 * 21],
        'box' => [PackingType::Box, 5.5, 2, 1500, 2 * 1500],
        'suit' => [PackingType::Suit, 6.5, 3, 1500, 3 * 1500],
    ]);

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
