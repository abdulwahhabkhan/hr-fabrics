<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting profile', function () {
    $this->get(route('profile.index'))->assertRedirect(route('login'));
});

test('authenticated user can view profile page', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $this->actingAs($user)
        ->get(route('profile.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/ProfileIndex')
            ->where('user.email', $user->email)
            ->where('role.name', $role->name)
        );
});

test('security page redirects to password confirmation when not confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.security'))
        ->assertRedirect(route('password.confirm'));
});

test('confirming password returns user to the security page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.security'));

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('profile.security'));

    $this->actingAs($user)
        ->get(route('profile.security'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Security')
            ->where('twoFactorEnabled', false)
            ->has('passkeys', 0)
        );
});

test('user can update password with valid data', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->from(route('profile.security'))
        ->put(route('profile.password'), [
            'current_password' => 'old-password-123',
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ]);

    $response->assertRedirect(route('profile.security'));
    $response->assertSessionHas('success');

    expect(Hash::check('new-secret-password-123', $user->refresh()->password))->toBeTrue();
});

test('password update fails when current password is wrong', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password-123', $user->refresh()->password))->toBeTrue();
});

test('password update fails when confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'old-password-123',
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'mismatched-password',
        ]);

    $response->assertSessionHasErrors('password');
    expect(Hash::check('old-password-123', $user->refresh()->password))->toBeTrue();
});

test('password update fails when password is too short', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'old-password-123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    $response->assertSessionHasErrors('password');
});
