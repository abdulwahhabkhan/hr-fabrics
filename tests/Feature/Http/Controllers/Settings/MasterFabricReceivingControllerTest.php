<?php

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Role;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function masterUser(): User
{
    return User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);
}

test('regular users cannot open the master tools even with every permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, ['settings.master.fabric-receivings.index', 'settings.master.fabric-receivings.reopen']);
    $receiving = FabricReceiving::factory()->confirmed()->create();

    actingAs($user)->get(route('settings.master.fabric-receivings.index'))->assertForbidden();
    actingAs($user)->post(route('settings.master.fabric-receivings.reopen', $receiving))->assertForbidden();

    expect($receiving->refresh()->status)->toBe(StatusText::Close);
});

test('master users see nothing until they search a reference number', function () {
    FabricReceiving::factory()->confirmed()->create();

    $response = actingAs(masterUser())->get(route('settings.master.fabric-receivings.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/Master/FabricReceivingReopenIndex')
        ->where('receiving', null)
    );
});

test('master users see the searched receiving with its vouchers and sales', function () {
    $receiving = FabricReceiving::factory()->confirmed()->create(['invoice_no' => 'ASN-2610001']);
    FabricReceiving::factory()->confirmed()->create(['invoice_no' => 'ASN-2610002']);
    $purchase = Purchase::factory()->confirmed()->create(['stock_ids' => (string) $receiving->id]);
    $order = Order::factory()->closed()->create();
    Inventory::factory()->withPurchase($receiving)->create([
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $order->id,
    ]);

    $response = actingAs(masterUser())
        ->get(route('settings.master.fabric-receivings.index', ['ref_no' => ' ASN-2610001 ']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('ref_no', 'ASN-2610001')
        ->where('receiving.id', $receiving->id)
        ->where('receiving.purchases.0.id', $purchase->id)
        ->where('receiving.sale_orders.0.id', $order->id)
        ->where('receiving.can_reopen', true)
    );
});

test('searching an unknown reference number finds nothing', function () {
    FabricReceiving::factory()->confirmed()->create(['invoice_no' => 'ASN-2610001']);

    $response = actingAs(masterUser())
        ->get(route('settings.master.fabric-receivings.index', ['ref_no' => 'ASN-261']));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('ref_no', 'ASN-261')
        ->where('receiving', null)
    );
});

test('master users reopen a receiving and land on its edit page', function () {
    $receiving = FabricReceiving::factory()->confirmed()->create();

    $response = actingAs(masterUser())->post(route('settings.master.fabric-receivings.reopen', $receiving));

    $response->assertRedirect(route('purchases.fabric-receivings.edit', $receiving));
    assertDatabaseHas(FabricReceiving::class, ['id' => $receiving->id, 'status' => StatusText::Open->value]);
});

test('master users cannot reopen a receiving whose stock was returned', function () {
    $receiving = FabricReceiving::factory()->confirmed()->create();
    Inventory::factory()->withPurchase($receiving)->create([
        'outbound_type' => PurchaseReturn::morphClass(),
        'outbound_id' => PurchaseReturn::factory()->create()->id,
    ]);

    $response = actingAs(masterUser())->post(route('settings.master.fabric-receivings.reopen', $receiving));

    $response->assertForbidden();
    expect($receiving->refresh()->status)->toBe(StatusText::Close);
});
