<?php

use App\Console\Commands\Util\CreateUserCommand;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

test('creates a user with the selected role and the entered password', function () {
    // arrange
    $role = Role::factory()->create(['name' => 'manager']);
    Role::factory()->create(['name' => 'owner']);

    // action
    artisan(CreateUserCommand::class)
        ->expectsQuestion('Name', 'Jane Doe')
        ->expectsQuestion('Email', 'jane@example.com')
        ->expectsChoice('Role', 'manager', ['manager', 'owner'])
        ->expectsQuestion('Password', 'Custom-Pass1')
        ->expectsOutput('User created successfully.')
        ->expectsOutput('Password: Custom-Pass1')
        ->assertSuccessful();

    // assert
    $user = User::query()->where('email', 'jane@example.com')->sole();
    expect($user->name)->toBe('Jane Doe')
        ->and($user->role_id)->toBe($role->id)
        ->and($user->password_changed_at)->not->toBeNull()
        ->and(Hash::check('Custom-Pass1', $user->password))->toBeTrue();
});

test('rejects a weak password', function () {
    // arrange
    Role::factory()->create(['name' => 'manager']);

    // action
    artisan(CreateUserCommand::class)
        ->expectsQuestion('Name', 'Jane Doe')
        ->expectsQuestion('Email', 'jane@example.com')
        ->expectsChoice('Role', 'manager', ['manager'])
        ->expectsQuestion('Password', 'weak')
        ->assertFailed();

    // assert
    expect(User::query()->count())->toBe(0);
});

test('rejects an email that is already taken', function () {
    // arrange
    Role::factory()->create(['name' => 'manager']);
    User::factory()->create(['email' => 'taken@example.com']);

    // action
    artisan(CreateUserCommand::class)
        ->expectsQuestion('Name', 'Jane Doe')
        ->expectsQuestion('Email', 'taken@example.com')
        ->expectsOutputToContain('The email has already been taken.')
        ->assertFailed();

    // assert
    expect(User::query()->count())->toBe(1);
});
