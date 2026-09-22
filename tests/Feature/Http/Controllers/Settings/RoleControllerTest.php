<?php

use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('roles list loaded', function () {

    Role::factory()->count(10)->create();
    $response = $this->get(route('settings.roles.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Role/RoleIndex')
        ->has('roles')
        ->has('roles.data', 11)
        ->has('filters')
    );
});

test('role form loaded', function ($action) {
    if ($action === 'create') {
        $user = null;
        $route = route('settings.roles.create');
    } else {
        $role = Role::factory()->create();
        $route = route('settings.roles.edit', $role->id);
    }

    $response = $this->get($route);
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Role/RoleForm')
        ->has('permissions')
        ->has('role')
        ->has('rolePermission')
    );
})->with(['create', 'update']);
