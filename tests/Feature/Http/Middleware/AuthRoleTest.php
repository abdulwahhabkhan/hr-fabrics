<?php

use App\Facades\Permission;

it('allows the request when the permission check passes', function (): void {
    Permission::fake(['catalog.brands.index' => true]);

    $user = $this->userWithoutPermissions();

    $response = $this->actingAs($user)->get(route('catalog.brands.index'));

    $response->assertOk();
    Permission::assertChecked('catalog.brands.index');
});

it('aborts with 403 when the permission check fails', function (): void {
    Permission::fake();

    $user = $this->userWithoutPermissions();

    $response = $this->actingAs($user)->get(route('catalog.brands.index'));

    $response->assertForbidden();
    Permission::assertChecked('catalog.brands.index');
});
