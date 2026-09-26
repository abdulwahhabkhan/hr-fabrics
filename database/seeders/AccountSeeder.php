<?php

namespace Database\Seeders;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\JournalHead;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\User;
use App\Services\AccountService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Seeds journal vouchers dated across last month and today: customer receipts,
 * supplier payments, expenses, bank deposits and drawings, so cash book, bank book,
 * expense and ledger reports have data.
 *
 * Runs after InboundSeeder and OutboundSeeder, reusing their customers and suppliers.
 */
class AccountSeeder extends Seeder
{
    use SeedsTransactions;

    private const int ENTRIES_LAST_MONTH = 30;

    private const array ENTRY_TYPES = [
        'customerReceipt',
        'supplierPayment',
        'expense',
        'bankDeposit',
        'drawing',
    ];

    /**
     * @var array{expenses: Collection<int, Account>, bank: Account, drawings: Account}
     */
    private array $accounts;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $this->accounts = $this->seedAccounts();

        $lastMonth = $this->transactionDates(self::ENTRIES_LAST_MONTH, 0)
            ->map(fn (CarbonImmutable $date) => [$date, fake()->randomElement(self::ENTRY_TYPES)]);
        $today = collect(self::ENTRY_TYPES)
            ->map(fn (string $type) => [CarbonImmutable::today(), $type]);

        $lastMonth->concat($today)->each(function (array $entry): void {
            [$date, $type] = $entry;
            DB::transaction(fn () => $this->{$type}($date));
        });
    }

    /**
     * @return array{expenses: Collection<int, Account>, bank: Account, drawings: Account}
     */
    private function seedAccounts(): array
    {
        $users = $this->getUsers();

        return [
            'expenses' => Account::factory(4)
                ->recycle($users)
                ->expense()
                ->sequence(
                    ['name' => 'Shop Rent'],
                    ['name' => 'Electricity'],
                    ['name' => 'Salaries'],
                    ['name' => 'Freight'],
                )
                ->create(),
            'bank' => Account::factory()->recycle($users)->bank()->create(['name' => 'Meezan Bank']),
            'drawings' => Account::factory()->recycle($users)->drawings()->create(['name' => 'Owner Drawings']),
        ];
    }

    /**
     * @throws Throwable
     */
    private function customerReceipt(CarbonImmutable $date): void
    {
        $customer = Account::query()->customers()->inRandomOrder()->first();

        $amount = $this->amount(5_000, 60_000);

        $this->post($date, 'Payment received from '.$customer->name)
            ->credit($customer->id, $amount)
            ->debit($this->cashAccountId(), $amount)
            ->logAction();
        $this->stampLatestJournal($date);
    }

    /**
     * @throws Throwable
     */
    private function supplierPayment(CarbonImmutable $date): void
    {
        $supplier = Account::query()->suppliers()->inRandomOrder()->first();

        $amount = $this->amount(10_000, 80_000);

        $this->post($date, 'Payment to '.$supplier->name)
            ->debit($supplier->id, $amount)
            ->credit($this->cashAccountId(), $amount)
            ->logAction();
        $this->stampLatestJournal($date);
    }

    /**
     * @throws Throwable
     */
    private function expense(CarbonImmutable $date): void
    {
        /** @var Account $account */
        $account = $this->accounts['expenses']->random();

        $amount = $this->amount(500, 15_000);

        $this->post($date, $account->name.' paid')
            ->debit($account->id, $amount)
            ->credit($this->cashAccountId(), $amount)
            ->logAction();
        $this->stampLatestJournal($date);
    }

    /**
     * @throws Throwable
     */
    private function bankDeposit(CarbonImmutable $date): void
    {
        $amount = $this->amount(20_000, 100_000);

        $this->post($date, 'Cash deposited in bank')
            ->credit($this->cashAccountId(), $amount)
            ->debit($this->accounts['bank']->id, $amount)
            ->logAction();
        $this->stampLatestJournal($date);
    }

    /**
     * @throws Throwable
     */
    private function drawing(CarbonImmutable $date): void
    {
        $amount = $this->amount(2_000, 20_000);

        $this->post($date, 'Owner withdrawal')
            ->debit($this->accounts['drawings']->id, $amount)
            ->credit($this->cashAccountId(), $amount)
            ->logAction();
        $this->stampLatestJournal($date);
    }

    /**
     * Random amount rounded to the hundred.
     */
    private function amount(int $min, int $max): int
    {
        return (int) (round(random_int($min, $max) / 100) * 100);
    }

    private function post(CarbonImmutable $date, string $detail): LedgerEntry
    {
        /** @var User $user */
        $user = $this->getUsers()->random();

        return resolve(LedgerEntry::class)
            ->setUser($user)
            ->setTransactionDate($date)
            ->setHead(JournalHead::Journal)
            ->setDetail($detail)
            ->init();
    }

    private function stampLatestJournal(CarbonImmutable $date): void
    {
        $this->stampTimestamps(Journal::query()->latest('id')->firstOrFail(), $date);
    }

    private function cashAccountId(): int
    {
        return resolve(AccountService::class)->getCashAccount()->id;
    }
}
