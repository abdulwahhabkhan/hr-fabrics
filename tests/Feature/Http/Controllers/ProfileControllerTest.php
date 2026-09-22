<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests are redirected to login when visiting profile', function () {
    $this->get(route('profile.index'))->assertRedirect(route('login'));
});

test('authenticated user can view profile page', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->get(route('profile.index'))
        ->assertOk();
});

test('user can update password with valid data', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create([
        'role_id' => $role->id,
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->put(route('profile.password'), [
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ]);

    $response->assertRedirect(route('profile.index'));
    $response->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('new-secret-password-123', $user->password))->toBeTrue();
});

test('password update fails when confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->put(route('profile.password'), [
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'mismatched-password',
        ]);

    $response->assertSessionHasErrors('password');
    $user->refresh();
    expect(Hash::check('old-password-123', $user->password))->toBeTrue();
});

test('password update fails when password is too short', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->put(route('profile.password'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    $response->assertSessionHasErrors('password');
});
