<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('regular users cannot open the master actions page even with the permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, ['settings.master.index']);

    actingAs($user)->get(route('settings.master.index'))->assertForbidden();
});

test('master users see the master actions page', function () {
    $master = User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);

    actingAs($master)->get(route('settings.master.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Master/MasterActionIndex'));
});
