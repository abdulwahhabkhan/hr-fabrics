<?php

namespace App\Console\Commands\Setup;

use App\Enums\AccountType;
use App\Models\Accounts\Account;
use Illuminate\Console\Command;

class ApplicationSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the application with its required default records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->createCashAccount();

        return self::SUCCESS;
    }

    protected function createCashAccount(): void
    {
        if (Account::query()->cashAccount()->exists()) {
            Account::query()->cashAccount()->nonSystemAccounts()->update(['system' => true]);

            $this->components->info('Cash account already exists.');

            return;
        }

        Account::query()->create([
            'name' => AccountType::CashAccount->value,
            'type' => AccountType::Assets,
            'created_by' => 0,
            'system' => true,
        ]);

        $this->components->info('Cash account created.');
    }
}
