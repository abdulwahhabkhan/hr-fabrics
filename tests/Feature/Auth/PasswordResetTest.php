<?php

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $this->get(route('password.request'))->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password link request does not reveal if user does not exist', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nonexistent@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

    Notification::assertNothingSent();
});

test('reset password link request fails with invalid email format', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $this->get(route('password.reset', $notification->token))->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();
    $oldHash = $user->password;

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });

    $user->refresh();
    expect(Hash::check('NewPassword123', $user->password))->toBeTrue()
        ->and($user->password_changed_at)->not->toBeNull()
        ->and($user->passwordHistories()->pluck('password')->all())->toBe([$oldHash]);
});

test('password cannot be reset to a recently used password', function () {
    Notification::fake();

    $user = User::factory()->create();
    PasswordHistory::factory()->for($user)->create(['password' => Hash::make('OlderSecret1')]);

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'OlderSecret1',
            'password_confirmation' => 'OlderSecret1',
        ])->assertSessionHasErrors('password');

        return true;
    });

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('email');
});
