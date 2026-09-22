<?php

namespace App\Console\Commands\Accounts;

use App\Jobs\Account\CalculateCustomerBalanceJob;
use App\Models\Accounts\Account;
use Illuminate\Console\Command;

class CalculateCustomerBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculate customer balance';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $customers = Account::customers()->get();
        foreach ($customers as $customer) {
            // get Balance
            CalculateCustomerBalanceJob::dispatch($customer);

        }
    }
}
