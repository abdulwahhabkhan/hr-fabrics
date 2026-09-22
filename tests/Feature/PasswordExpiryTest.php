<?php

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['password-policy.expiry_days' => 90, 'password-policy.history_count' => 3]);
});

test('password expires after configured days', function () {
    $fresh = User::factory()->create(['password_changed_at' => now()->subDays(89)]);
    $stale = User::factory()->create(['password_changed_at' => now()->subDays(91)]);

    expect($fresh->isPasswordExpired())->toBeFalse()
        ->and($stale->isPasswordExpired())->toBeTrue();
});

test('expiry can be disabled', function () {
    config(['password-policy.expiry_days' => 0]);
    $user = User::factory()->create(['password_changed_at' => now()->subYears(5)]);

    expect($user->isPasswordExpired())->toBeFalse();
});

test('expired user is redirected to profile but can open it', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(100)]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('profile.index'))
        ->assertSessionHas('error');

    $this->actingAs($user)->get(route('profile.index'))->assertOk();
});

test('active user is not redirected', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(10)]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('changing password archives old hash and resets expiry', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(100)]);
    $oldHash = $user->password;

    $this->actingAs($user)->put(route('profile.password'), [
        'password' => 'NewSecret123',
        'password_confirmation' => 'NewSecret123',
    ])->assertRedirect(route('profile.index'));

    $user->refresh();
    expect(Hash::check('NewSecret123', $user->password))->toBeTrue()
        ->and($user->isPasswordExpired())->toBeFalse()
        ->and($user->passwordHistories()->pluck('password')->all())->toBe([$oldHash]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('reusing current or previous password is rejected', function () {
    $user = User::factory()->create();
    PasswordHistory::factory()->for($user)->create(['password' => Hash::make('OlderSecret1')]);

    foreach (['password', 'OlderSecret1'] as $reused) {
        $this->actingAs($user)->put(route('profile.password'), [
            'password' => $reused,
            'password_confirmation' => $reused,
        ])->assertSessionHasErrors('password');
    }
});

test('password older than history window can be reused', function () {
    $user = User::factory()->create();
    PasswordHistory::factory()->for($user)->create(['password' => Hash::make('AncientSecret1')]);
    PasswordHistory::factory()->count(3)->for($user)->create();

    expect($user->hasUsedPassword('AncientSecret1'))->toBeFalse();
});

test('history is trimmed to configured count', function () {
    $user = User::factory()->create();

    foreach (['Secret111a', 'Secret222b', 'Secret333c', 'Secret444d', 'Secret555e'] as $plain) {
        $user->changePassword($plain);
    }

    expect($user->passwordHistories()->count())->toBe(3);
});
