<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('users list loaded', function () {

    User::factory()->count(10)->create();
    $response = $this->get(route('settings.users.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/User/UserIndex')
        ->has('users')
        ->has('users.data', 11)
        ->has('filters')
    );
});

test('user form loaded', function ($action) {
    if ($action === 'create') {
        $user = null;
        $route = route('settings.users.create');
    } else {
        $user = User::factory()->create();
        $route = route('settings.users.edit', $user->id);
    }

    $response = $this->get($route);
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/User/UserForm')
        ->has('user')
        ->has('roles')
    );
})->with(['create', 'update']);
