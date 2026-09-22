<?php

use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;

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
