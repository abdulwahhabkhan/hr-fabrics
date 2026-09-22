<?php

use function Pest\Laravel\actingAs;

it('allows an ability when the user has the matching permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'reports.export');
    actingAs($user);

    expect($user->can('reports.export'))->toBeTrue();
});

it('denies an ability when the user lacks the matching permission', function () {
    $user = $this->userWithoutPermissions();
    actingAs($user);

    expect($user->can('reports.export'))->toBeFalse();
});
