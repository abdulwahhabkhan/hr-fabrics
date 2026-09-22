<?php

use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;

test('delete order line item', function () {
    // Arrange
    $this->withoutExceptionHandling();
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.orders.update');
    $order = Order::factory()->create();
    $total = [
        'id' => $order->id,
        'total' => 1500,
        'total_qty' => 5.5,
        'net_total' => 1500,
    ];
    $item = OrderItem::factory()->suit()->create([
        'order_id' => $order->id,
        'qty' => 1,
        'size' => 5.5,
        'total_qty' => 5.5,
        'total_amount' => 1500,
    ]);
    $item_2 = OrderItem::factory()->suit()->create(['order_id' => $order->id]);

    // Act
    $response = $this->actingAs($user)
        ->delete(route('ajax.so.item.destroy', $item_2->id));

    // Assert
    $response->assertOk();
    $this->assertDatabaseCount(OrderItem::class, 1);
    $this->assertDatabaseHas(OrderItem::class, $item->toArray());
    $this->assertDatabaseHas(Order::class, $total);

});
