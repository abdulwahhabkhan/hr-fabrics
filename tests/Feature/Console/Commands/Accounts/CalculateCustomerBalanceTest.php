<?php

use App\Console\Commands\Accounts\CalculateCustomerBalance;
use App\Jobs\Account\CalculateCustomerBalanceJob;
use App\Models\Accounts\Account;

use function Pest\Laravel\artisan;

test('job triggered for customer', function () {
    // arrange
    Queue::fake();
    Account::factory(4)->customer()->create();
    // action
    artisan(CalculateCustomerBalance::class);
    // assert
    Queue::assertPushed(CalculateCustomerBalanceJob::class, 4);

});
