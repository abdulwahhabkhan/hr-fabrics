<?php

use App\Enums\OrderStatus;
use App\Models\Accounts\Journal;
use App\Models\Sales\Order;
use Inertia\Testing\AssertableInertia as Assert;

test('order ledger detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.orders.ledger');
    $order = Order::factory()->closed()->create();
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $order->getMorphClass(),
            'resource_id' => $order->id,
        ]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.orders.ledger', $order));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/JournalSummary')
        ->has('journal')
        ->has('back_url')
        ->has('page_header')
    );
});

test('order ledger detail is forbidden for an open order', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.orders.ledger');
    $order = Order::factory()->create(['status' => OrderStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.orders.ledger', $order));

    // Assert
    $response->assertForbidden();
});
