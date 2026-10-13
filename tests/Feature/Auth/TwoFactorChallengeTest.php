<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

/**
 * Enable and confirm real two-factor credentials, returning a currently valid TOTP code.
 */
function enableTwoFactor(User $user): string
{
    app(EnableTwoFactorAuthentication::class)($user);

    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return (new Google2FA)->getCurrentOtp(
        Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret)
    );
}

test('two factor challenge redirects to login when not authenticated', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/two-factor-challenge'));
});

test('a valid two factor code completes the login', function () {
    $user = User::factory()->create();
    $code = enableTwoFactor($user);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('an invalid two factor code does not complete the login', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.login.store'), ['code' => '000000']);

    $this->assertGuest();
});

test('a valid recovery code completes the login', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);
    $recoveryCode = $user->fresh()->recoveryCodes()[0];

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.login.store'), ['recovery_code' => $recoveryCode]);

    $this->assertAuthenticatedAs($user);
});
