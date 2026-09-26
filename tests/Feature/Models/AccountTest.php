<?php

use App\Models\Accounts\Account;

test('partners scope includes accounts listed in config', function (string $format) {
    // Arrange
    $listed = Account::factory()->expense()->count(2)->create();
    $unlisted = Account::factory()->expense()->create();
    $partner = Account::factory()->partner()->create();
    config(['store.partners_ids' => sprintf($format, ...$listed->pluck('id'))]);

    // Act
    $partnerIds = Account::query()->partners()->pluck('id');

    // Assert
    expect($partnerIds)->toContain($partner->id, ...$listed->pluck('id'))
        ->not->toContain($unlisted->id);
})->with([
    'comma separated' => ['%d,%d'],
    'comma separated with spaces and trailing comma' => [' %d , %d, '],
]);

test('partners scope accepts a single integer id from config', function () {
    // Arrange
    $listed = Account::factory()->expense()->create();
    config(['store.partners_ids' => $listed->id]);

    // Act
    $partnerIds = Account::query()->partners()->pluck('id');

    // Assert
    expect($partnerIds)->toContain($listed->id);
});
