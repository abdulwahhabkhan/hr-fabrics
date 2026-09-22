<?php

use App\Jobs\Account\CalculateCustomerBalanceJob;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;

use function Pest\Laravel\assertDatabaseHas;

test('customer balance is calculated and updated to the accounts table', function () {
    // arrange
    $customer = Account::factory()->customer()->create();
    $total = 1000;
    // sequence
    $sequence = [
        ['posted_at' => now()->subDays(3)],
        ['posted_at' => now()->subDays(5)],
        ['posted_at' => now()->subDays(10)],
        ['posted_at' => now()->subDays(15)],
        ['posted_at' => now()],
    ];
    // add sale entries

    Journal::factory(5)
        ->hasTransactions(1, [
            'account_id' => $customer->id,
            'dr' => $total,
            'cr' => 0,
        ])
        ->sequence(...$sequence)
        ->create();

    Journal::factory()
        ->hasTransactions(1, [
            'account_id' => $customer->id,
            'cr' => $total * 2,
            'dr' => 0,
        ])
        ->create();
    // action
    CalculateCustomerBalanceJob::dispatchSync($customer);
    // assert
    assertDatabaseHas(Account::class, [
        'id' => $customer->id,
        'balance' => $total * 3,
        'balance_date' => $sequence[2]['posted_at']?->toDateString(), // no 3 date
    ]);

});
