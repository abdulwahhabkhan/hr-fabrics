<?php

use App\Models\Purchase\Purchase;
use App\Models\Stock\Inventory;

use function Pest\Laravel\actingAs;

it('forbids update when user lacks the update permission', function () {
    $user = $this->userWithoutPermissions();
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('update', $receipt))->toBeFalse();
});

it('forbids update when receipt is not open even with the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.update');
    $receipt = Purchase::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('update', $receipt))->toBeFalse();
});

it('allows update on an open receipt when user has the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.update');
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('update', $receipt))->toBeTrue();
});

it('forbids delete on a closed receipt', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.destroy');
    $receipt = Purchase::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('delete', $receipt))->toBeFalse();
});

it('allows delete on an open receipt when user has the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.destroy');
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('delete', $receipt))->toBeTrue();
});

it('forbids unlock when receipt is open', function () {
    $user = $this->userWithoutPermissions();
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('unlock', $receipt))->toBeFalse();
});

it('allows unlock when the confirmed receipt was updated today', function () {
    $user = $this->userWithoutPermissions();
    $receipt = Purchase::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('unlock', $receipt))->toBeTrue();
});

it('forbids inventory on an open receipt', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.inventory');
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('inventory', $receipt))->toBeFalse();
});

it('allows inventory on a confirmed receipt when user has the inventory permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.inventory');
    $receipt = Purchase::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('inventory', $receipt))->toBeTrue();
});

it('forbids ledger on an open receipt', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.ledger');
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('ledger', $receipt))->toBeFalse();
});

it('allows ledger on a confirmed receipt when user has the ledger permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.pos.ledger');
    $receipt = Purchase::factory()->confirmed()->create();
    actingAs($user);

    expect($user->can('ledger', $receipt))->toBeTrue();
});

it('denies check-inventory when the receipt is open', function () {
    $user = $this->userWithoutPermissions();
    $receipt = Purchase::factory()->create();
    actingAs($user);

    expect($user->can('check-inventory', $receipt))->toBeFalse();
});

it('allows check-inventory when the confirmed receipt has no booked stock', function () {
    $user = $this->userWithoutPermissions();
    $stock = \App\Models\Purchase\FabricReceiving::factory()->create();
    $receipt = Purchase::factory()->confirmed()->create(['stock_ids' => (string) $stock->id]);
    actingAs($user);

    expect($user->can('check-inventory', $receipt))->toBeTrue();
});

it('denies check-inventory when the confirmed receipt has booked stock', function () {
    $user = $this->userWithoutPermissions();
    $stock = \App\Models\Purchase\FabricReceiving::factory()->create();
    $receipt = Purchase::factory()->confirmed()->create(['stock_ids' => (string) $stock->id]);
    Inventory::factory()->create([
        'stockable_type' => $stock->getMorphClass(),
        'stockable_id' => $stock->id,
        'stockable_item_id' => 1,
        'outbound_id' => 999,
        'outbound_type' => 'test',
    ]);
    actingAs($user);

    expect($user->can('check-inventory', $receipt))->toBeFalse();
});
