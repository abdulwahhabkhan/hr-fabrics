<?php

use App\Models\Role;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

it('allows view when user has the show permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.show');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('view', $order))->toBeTrue();
});

it('forbids view when user lacks the show permission', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('view', $order))->toBeFalse();
});

it('allows update on an open order when user has the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.update');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('update', $order))->toBeTrue();
});

it('forbids update on a closed order', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.update');
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('update', $order))->toBeFalse();
});

it('forbids update when user lacks the update permission', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('update', $order))->toBeFalse();
});

it('allows delete on an open order when user has the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.destroy');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('delete', $order))->toBeTrue();
});

it('forbids delete on a closed order', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.destroy');
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('delete', $order))->toBeFalse();
});

it('forbids delete when user lacks the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('delete', $order))->toBeFalse();
});

it('forbids bilti when order is open', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('bilti', $order))->toBeFalse();
});

it('allows bilti when order is closed', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('bilti', $order))->toBeTrue();
});

it('forbids unlock when order is not closed', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('unlock', $order))->toBeFalse();
});

it('allows unlock when the closed order was updated today', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('unlock', $order))->toBeTrue();
});

it('forbids unlock when the closed order was updated on a different day', function () {
    $user = $this->userWithoutPermissions();
    $order = Order::factory()->closed()->create();
    $order->timestamps = false;
    $order->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
    actingAs($user);

    expect($user->can('unlock', $order))->toBeFalse();
});

it('forbids gate-pass on an open order', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.gate-pass');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('gate-pass', $order))->toBeFalse();
});

it('allows gate-pass on a closed order when user has the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.gate-pass');
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('gate-pass', $order))->toBeTrue();
});

it('forbids ledger on an open order', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.ledger');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('ledger', $order))->toBeFalse();
});

it('allows ledger on a closed order when user has the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.ledger');
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('ledger', $order))->toBeTrue();
});

it('forbids inventory on an open order', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.inventory');
    $order = Order::factory()->create();
    actingAs($user);

    expect($user->can('inventory', $order))->toBeFalse();
});

it('allows inventory on a closed order when user has the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.orders.inventory');
    $order = Order::factory()->closed()->create();
    actingAs($user);

    expect($user->can('inventory', $order))->toBeTrue();
});

it('allows a master user to allocate a confirmed order that is not fully allocated', function () {
    $user = User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);
    $order = Order::factory()->closed()->create();

    expect($user->can('masterAllocate', $order))->toBeTrue();
});

it('refuses master allocate', function (Closure $arrange, bool $isMaster, string $message) {
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id, 'is_master' => $isMaster]);
    $order = $arrange();

    $response = Gate::forUser($user)->inspect('masterAllocate', $order);

    expect($response->denied())->toBeTrue()
        ->and((string) $response->message())->toBe($message);
})->with([
    'not a master user' => [fn () => Order::factory()->closed()->create(), false, 'Only master users can allocate orders'],
    'order still open' => [fn () => Order::factory()->create(), true, 'Order is not confirmed'],
    'order already allocated' => [fn () => Order::factory()->closed()->allocated()->create(), true, 'Order is already allocated'],
]);
