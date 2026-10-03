<?php

use App\Models\Accounts\Account;

use function Pest\Laravel\actingAs;

it('forbids update on a system account even with the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'accounts.accounts.update');
    $account = Account::factory()->expense()->system()->create();
    actingAs($user);

    expect($user->can('update', $account))->toBeFalse();
});

it('forbids update when user lacks the update permission', function () {
    $user = $this->userWithoutPermissions();
    $account = Account::factory()->expense()->create();
    actingAs($user);

    expect($user->can('update', $account))->toBeFalse();
});

it('allows update on a non-system account when user has the update permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'accounts.accounts.update');
    $account = Account::factory()->expense()->create();
    actingAs($user);

    expect($user->can('update', $account))->toBeTrue();
});

it('forbids delete on a system account even with the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'accounts.accounts.destroy');
    $account = Account::factory()->expense()->system()->create();
    actingAs($user);

    expect($user->can('delete', $account))->toBeFalse();
});

it('allows delete on a non-system account when user has the destroy permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'accounts.accounts.destroy');
    $account = Account::factory()->expense()->create();
    actingAs($user);

    expect($user->can('delete', $account))->toBeTrue();
});
