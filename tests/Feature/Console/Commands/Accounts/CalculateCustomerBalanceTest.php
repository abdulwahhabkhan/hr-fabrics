<?php

use App\Console\Commands\Accounts\CalculateCustomerBalanceCommand;
use App\Jobs\Account\CalculateCustomerBalanceJob;
use App\Models\Accounts\Account;

use function Pest\Laravel\artisan;

test('job triggered for customer', function () {
    // arrange
    Queue::fake();
    Account::factory(4)->customer()->create();
    // action
    artisan(CalculateCustomerBalanceCommand::class);
    // assert
    Queue::assertPushed(CalculateCustomerBalanceJob::class, 4);

});
