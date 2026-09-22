<?php

use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;

use function Pest\Laravel\actingAs;

it('allows view when user has the show permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.show');
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('view', $return))->toBeTrue();
});

it('allows create when user has the create permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.create');
    actingAs($user);

    expect($user->can('create', SalesReturn::class))->toBeTrue();
});

it('allows update on an open return when user has the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.update');
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('update', $return))->toBeTrue();
});

it('forbids update on a closed return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.update');
    $return = SalesReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('update', $return))->toBeFalse();
});

it('allows delete when user has the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.destroy');
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('delete', $return))->toBeTrue();
});

it('forbids unlock when return is not closed', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('unlock', $return))->toBeFalse();
});

it('allows unlock when the closed return was updated today', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('unlock', $return))->toBeTrue();
});

it('forbids unlock when the closed return was updated on a different day', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->closed()->create();
    $return->timestamps = false;
    $return->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
    actingAs($user);

    expect($user->can('unlock', $return))->toBeFalse();
});

it('forbids ledger on an open return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.ledger');
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('ledger', $return))->toBeFalse();
});

it('allows ledger on a closed return when user has the ledger permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.ledger');
    $return = SalesReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('ledger', $return))->toBeTrue();
});

it('forbids inventory on an open return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.inventory');
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('inventory', $return))->toBeFalse();
});

it('allows inventory on a closed return when user has the inventory permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'sales.returns.inventory');
    $return = SalesReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('inventory', $return))->toBeTrue();
});

it('denies check-inventory when the return is not closed', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->create();
    actingAs($user);

    expect($user->can('check-inventory', $return))->toBeFalse();
});

it('allows check-inventory when the closed return has no booked stock', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('check-inventory', $return))->toBeTrue();
});

it('denies check-inventory when the closed return has booked stock', function () {
    $user = $this->userWithoutPermissions();
    $return = SalesReturn::factory()->closed()->create();
    Inventory::factory()->create([
        'stockable_type' => $return->getMorphClass(),
        'stockable_id' => $return->id,
        'stockable_item_id' => 1,
        'outbound_id' => 999,
        'outbound_type' => 'test',
    ]);
    actingAs($user);

    expect($user->can('check-inventory', $return))->toBeFalse();
});
