<?php

use App\Models\User;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

function enableTwoFactor(User $user): string
{
    app(EnableTwoFactorAuthentication::class)($user);

    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return (new Google2FA)->getCurrentOtp(
        Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret)
    );
}

test('login redirects to the two factor challenge without logging in', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('a valid two factor code completes the login', function () {
    $user = User::factory()->create();
    $code = enableTwoFactor($user);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post('/two-factor-challenge', [
        'code' => $code,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(App\Providers\AppServiceProvider::HOME);
});

test('an invalid two factor code does not complete the login', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post('/two-factor-challenge', [
        'code' => '000000',
    ]);

    $this->assertGuest();
});

test('a valid recovery code completes the login', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);
    $recoveryCode = $user->fresh()->recoveryCodes()[0];

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post('/two-factor-challenge', [
        'recovery_code' => $recoveryCode,
    ]);

    $this->assertAuthenticatedAs($user);
});
