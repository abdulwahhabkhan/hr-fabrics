<?php

use App\Models\Purchase\PurchaseReturn;

use function Pest\Laravel\actingAs;

it('allows update on an open return when user has the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.update');
    $return = PurchaseReturn::factory()->create();
    actingAs($user);

    expect($user->can('update', $return))->toBeTrue();
});

it('forbids update on a closed return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.update');
    $return = PurchaseReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('update', $return))->toBeFalse();
});

it('forbids unlock when return is open', function () {
    $user = $this->userWithoutPermissions();
    $return = PurchaseReturn::factory()->create();
    actingAs($user);

    expect($user->can('unlock', $return))->toBeFalse();
});

it('allows unlock when return was closed today', function () {
    $user = $this->userWithoutPermissions();
    $return = PurchaseReturn::factory()->closed()->create(['transaction_date' => now()]);
    actingAs($user);

    expect($user->can('unlock', $return))->toBeTrue();
});

it('forbids unlock when the closed return was updated on a different day', function () {
    $user = $this->userWithoutPermissions();
    $return = PurchaseReturn::factory()->closed()->create();
    $return->timestamps = false;
    $return->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
    actingAs($user);

    expect($user->can('unlock', $return))->toBeFalse();
});

it('forbids ledger on an open return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.ledger');
    $return = PurchaseReturn::factory()->create();
    actingAs($user);

    expect($user->can('ledger', $return))->toBeFalse();
});

it('allows ledger on a closed return when user has the ledger permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.ledger');
    $return = PurchaseReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('ledger', $return))->toBeTrue();
});

it('forbids inventory on an open return', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.inventory');
    $return = PurchaseReturn::factory()->create();
    actingAs($user);

    expect($user->can('inventory', $return))->toBeFalse();
});

it('allows inventory on a closed return when user has the inventory permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.por.inventory');
    $return = PurchaseReturn::factory()->closed()->create();
    actingAs($user);

    expect($user->can('inventory', $return))->toBeTrue();
});
