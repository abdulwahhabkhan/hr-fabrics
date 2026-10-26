<?php

use App\Enums\OrderAllocation;
use App\Models\Catalog\Product;
use App\Models\Role;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

function masterAllocator(): User
{
    return User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);
}

test('regular users cannot open or use the allocate order tool even with every permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, ['settings.master.orders.index', 'settings.master.orders.allocate']);
    $order = Order::factory()->closed()->create();

    actingAs($user)->get(route('settings.master.orders.index'))->assertForbidden();
    actingAs($user)->post(route('settings.master.orders.allocate', $order))->assertForbidden();

    expect($order->refresh()->allocation)->toBe(OrderAllocation::Unallocated);
});

test('master users see nothing until they search an invoice number', function () {
    Order::factory()->closed()->create();

    $response = actingAs(masterAllocator())->get(route('settings.master.orders.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/Master/OrderAllocateIndex')
        ->where('order', null)
    );
});

test('master users see the searched order with its required and allocated meters', function () {
    $order = Order::factory()->closed()->create(['invoice_no' => '261000001']);
    Order::factory()->closed()->create(['invoice_no' => '261000002']);
    $item = OrderItem::factory()->thaan()->create(['order_id' => $order->id, 'qty' => 1, 'size' => 20, 'total_qty' => 20]);
    Inventory::factory()->thaan()->withPurchase()->create([
        'product_id' => $item->product_id,
        'meters' => 8,
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $order->id,
        'outbound_item_id' => $item->id,
    ]);

    $response = actingAs(masterAllocator())
        ->get(route('settings.master.orders.index', ['ref_no' => ' 261000001 ']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('ref_no', '261000001')
        ->where('order.id', $order->id)
        ->where('order.required_meters', 20)
        ->where('order.allocated_meters', 8)
        ->where('order.items.0.allocated_meters', 8)
        ->where('order.can_allocate', true)
    );
});

test('searching an unknown invoice number finds nothing', function () {
    Order::factory()->closed()->create(['invoice_no' => '261000001']);

    $response = actingAs(masterAllocator())
        ->get(route('settings.master.orders.index', ['ref_no' => '2610']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('ref_no', '2610')
        ->where('order', null)
    );
});

test('master users allocate an order and return to it', function () {
    $product = Product::factory()->create();
    Inventory::factory()->thaan()->withPurchase()->create(['product_id' => $product->id, 'qty' => 2, 'meters' => 50]);
    $order = Order::factory()->closed()->create();
    OrderItem::factory()->thaan()->create(['order_id' => $order->id, 'product_id' => $product->id, 'qty' => 1, 'size' => 20, 'total_qty' => 20]);

    $response = actingAs(masterAllocator())->post(route('settings.master.orders.allocate', $order));

    $response->assertRedirect(route('settings.master.orders.index', ['ref_no' => $order->invoice_no]))
        ->assertSessionHas('success', "Order {$order->invoice_no} allocated.");
    expect($order->refresh()->allocation)->toBe(OrderAllocation::Allocated);
});

test('allocating an order short of stock returns the stock error', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->closed()->create();
    OrderItem::factory()->thaan()->create(['order_id' => $order->id, 'product_id' => $product->id, 'qty' => 1, 'size' => 20, 'total_qty' => 20]);

    $response = actingAs(masterAllocator())->post(route('settings.master.orders.allocate', $order));

    $response->assertSessionHasErrors('product');
    expect($order->refresh()->allocation)->toBe(OrderAllocation::Unallocated);
});

test('master users cannot allocate an order that is already allocated', function () {
    $order = Order::factory()->closed()->allocated()->create();

    actingAs(masterAllocator())->post(route('settings.master.orders.allocate', $order))->assertForbidden();
});
