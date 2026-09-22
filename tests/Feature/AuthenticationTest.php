<?php

use App\Models\LoginActivity;
use App\Models\User;
use App\Providers\AppServiceProvider;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(AppServiceProvider::HOME);
    $this->assertDatabaseCount(LoginActivity::class, 1);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseCount(LoginActivity::class, 1);
});

test('registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
