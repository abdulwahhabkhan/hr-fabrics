<?php

it('reports true for a permission attached to the user role', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'inventory.adjust');

    expect($user->checkPermissionTo('inventory.adjust'))->toBeTrue();
});

it('reports false for a permission not attached to the user role', function () {
    $user = $this->userWithoutPermissions();

    expect($user->checkPermissionTo('inventory.adjust'))->toBeFalse();
});

it('hasPermission reflects the same permission set as checkPermissionTo', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'inventory.adjust');

    expect($user->hasPermission('inventory.adjust'))->toBeTrue();
});
