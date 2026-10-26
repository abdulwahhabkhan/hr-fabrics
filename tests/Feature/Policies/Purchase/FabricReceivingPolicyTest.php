<?php

use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Role;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

it('allows edit when stock is open', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('edit', $stock))->toBeTrue();
});

it('forbids edit when stock is not open', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('edit', $stock))->toBeFalse();
});

it('forbids delete when user lacks the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('delete', $stock))->toBeFalse();
});

it('forbids delete when stock is not open even with the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.destroy');
    $stock = FabricReceiving::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('delete', $stock))->toBeFalse();
});

it('allows delete on an open stock when user has the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.destroy');
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('delete', $stock))->toBeTrue();
});

it('forbids unlock when stock is open', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('unlock', $stock))->toBeFalse();
});

it('allows unlock when the confirmed stock was updated today', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->confirmed()->create(['transaction_date' => now()]);
    actingAs($user);

    expect($user->can('unlock', $stock))->toBeTrue();
});

it('forbids inventory when user lacks the permission', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('inventory', $stock))->toBeFalse();
});

it('forbids inventory when stock is not closed even with the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.inventory');
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('inventory', $stock))->toBeFalse();
});

it('allows inventory on a closed stock when user has the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.inventory');
    $stock = FabricReceiving::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('inventory', $stock))->toBeTrue();
});

it('denies check-inventory when the stock is open', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->create();
    actingAs($user);

    expect($user->can('check-inventory', $stock))->toBeFalse();
});

it('allows check-inventory when the confirmed stock has no booked inventory', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('check-inventory', $stock))->toBeTrue();
});

it('denies check-inventory when the confirmed stock has booked inventory', function () {
    $user = $this->userWithoutPermissions();
    $stock = FabricReceiving::factory()->confirmed()->create();
    Inventory::factory()->create([
        'stockable_type' => $stock->getMorphClass(),
        'stockable_id' => $stock->id,
        'stockable_item_id' => 1,
        'outbound_id' => 999,
        'outbound_type' => 'test',
    ]);
    actingAs($user);

    expect($user->can('check-inventory', $stock))->toBeFalse();
});

it('limits the master tools to master users', function (bool $isMaster) {
    $user = $this->userWithoutPermissions();
    $user->forceFill(['is_master' => $isMaster])->save();

    expect($user->can('masterTools', FabricReceiving::class))->toBe($isMaster);
})->with([
    'master user' => [true],
    'regular user' => [false],
]);

it('allows a master user to reopen a confirmed receiving that is invoiced and sold', function () {
    $user = User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);
    $stock = FabricReceiving::factory()->confirmed()->create();
    Purchase::factory()->confirmed()->create(['stock_ids' => (string) $stock->id]);
    $order = Order::factory()->closed()->create();
    Inventory::factory()->withPurchase($stock)->create([
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $order->id,
    ]);

    expect($user->can('masterReopen', $stock))->toBeTrue();
});

it('refuses master reopen', function (Closure $arrange, bool $isMaster, string $message) {
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id, 'is_master' => $isMaster]);
    $stock = $arrange();

    $response = Gate::forUser($user)->inspect('masterReopen', $stock);

    expect($response->denied())->toBeTrue()
        ->and((string) $response->message())->toBe($message);
})->with([
    'not a master user' => [fn () => FabricReceiving::factory()->confirmed()->create(), false, 'Only master users can reopen receivings'],
    'receiving still open' => [fn () => FabricReceiving::factory()->create(), true, 'Receiving is not confirmed'],
    'stock returned to supplier' => [function () {
        $stock = FabricReceiving::factory()->confirmed()->create();
        Inventory::factory()->withPurchase($stock)->create([
            'outbound_type' => PurchaseReturn::morphClass(),
            'outbound_id' => PurchaseReturn::factory()->create()->id,
        ]);

        return $stock;
    }, true, 'Stock is returned to the supplier'],
    'voucher has a purchase return' => [function () {
        $stock = FabricReceiving::factory()->confirmed()->create();
        $purchase = Purchase::factory()->confirmed()->create(['stock_ids' => (string) $stock->id]);
        PurchaseReturn::factory()->create(['purchase_id' => $purchase->id]);

        return $stock;
    }, true, 'Stock is returned to the supplier'],
    'stock transferred' => [function () {
        $stock = FabricReceiving::factory()->confirmed()->create();
        Inventory::factory()->withPurchase($stock)->create([
            'outbound_type' => StockTransfer::morphClass(),
            'outbound_id' => 1,
        ]);

        return $stock;
    }, true, 'Stock is transferred or used outside sales'],
    'sale of the stock is returned' => [function () {
        $stock = FabricReceiving::factory()->confirmed()->create();
        $order = Order::factory()->closed()->create();
        Inventory::factory()->withPurchase($stock)->create([
            'outbound_type' => Order::morphClass(),
            'outbound_id' => $order->id,
        ]);
        SalesReturn::factory()->create(['order_no' => $order->invoice_no]);

        return $stock;
    }, true, 'A sale of this stock is returned'],
]);
