<?php

use App\Console\Commands\Setup\ApplicationSetupCommand;
use App\Enums\AccountType;
use App\Models\Accounts\Account;

use function Pest\Laravel\artisan;

test('creates the cash account as a system asset', function () {
    // action
    artisan(ApplicationSetupCommand::class)
        ->expectsOutputToContain('Cash account created.')
        ->assertSuccessful();

    // assert
    $account = Account::query()->sole();
    expect($account->name)->toBe(AccountType::CashAccount->value)
        ->and($account->type)->toBe(AccountType::Assets)
        ->and($account->system)->toBeTrue();
});

test('marks an existing cash account as system without creating another', function () {
    // arrange
    $existing = Account::factory()->cash()->create();

    // action
    artisan(ApplicationSetupCommand::class)
        ->expectsOutputToContain('Cash account already exists.')
        ->assertSuccessful();

    // assert
    $account = Account::query()->sole();
    expect($account->id)->toBe($existing->id)
        ->and($account->system)->toBeTrue();
});
