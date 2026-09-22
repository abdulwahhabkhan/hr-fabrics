<?php

use App\Enums\AccountType;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Services\AccountService;

beforeEach(function () {
    $this->service = new AccountService;
});

function postJournalLine(Account $account, array $journalAttributes = [], array $detailAttributes = []): JournalDetail
{
    $journal = Journal::factory()->create($journalAttributes);

    return JournalDetail::factory()->create(array_merge([
        'journal_id' => $journal->id,
        'account_id' => $account->id,
    ], $detailAttributes));
}

it('sums debits and credits into the account balance', function () {
    $account = Account::factory()->create();
    postJournalLine($account, [], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, [], ['dr' => 0, 'cr' => 200]);

    expect($this->service->getAccountBalance($account->id))->toBe(300);
});

it('returns the detailed breakdown of an account balance', function () {
    $account = Account::factory()->create();
    postJournalLine($account, [], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, [], ['dr' => 0, 'cr' => 200]);

    expect($this->service->getAccountBalanceDetailed($account->id))->toBe([
        'balance' => 300,
        'debit' => 500,
        'credit' => 200,
    ]);
});

it('finds the cash account by name', function () {
    $cash = Account::factory()->cash()->create();

    expect($this->service->getCashAccount()->id)->toBe($cash->id);
});

it('sums customer payments received for the journal head within the date range', function () {
    $customer = Account::factory()->customer()->create();
    postJournalLine($customer, ['head' => 'journal', 'posted_at' => today()], ['dr' => 0, 'cr' => 1000]);
    postJournalLine($customer, ['head' => 'journal', 'posted_at' => today()->subDays(10)], ['dr' => 0, 'cr' => 999]);
    postJournalLine($customer, ['head' => 'sales', 'posted_at' => today()], ['dr' => 0, 'cr' => 999]);

    $total = $this->service->getTotalPaymentReceived(today()->startOfDay(), today()->endOfDay());

    expect($total)->toEqual(1000);
});

it('sums cash sales for the cash account within the given day', function () {
    $cash = Account::factory()->cash()->create();
    postJournalLine($cash, ['head' => 'sales', 'posted_at' => today()], ['dr' => 400, 'cr' => 0]);
    postJournalLine($cash, ['head' => 'sales', 'posted_at' => today()->subDay()], ['dr' => 999, 'cr' => 0]);

    expect($this->service->getCashSales(today()))->toBe(400);
});

it('sums the balance strictly before the given date', function () {
    $account = Account::factory()->create();
    postJournalLine($account, ['posted_at' => today()->subDay()], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, ['posted_at' => today()], ['dr' => 300, 'cr' => 0]);

    expect($this->service->balanceBefore($account->id, today()))->toEqual(500);
});

it('sums the balance up to and including the given date', function () {
    $account = Account::factory()->create();
    postJournalLine($account, ['posted_at' => today()->subDay()], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, ['posted_at' => today()], ['dr' => 300, 'cr' => 0]);

    expect($this->service->balanceOn($account->id, today()))->toEqual(800);
});

it('sums the balance by account type strictly before the given date', function () {
    $account = Account::factory()->expense()->create();
    postJournalLine($account, ['posted_at' => today()->subDay()], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, ['posted_at' => today()], ['dr' => 300, 'cr' => 0]);

    expect($this->service->balanceBeforeByType(AccountType::Expenses, today()))->toBe(500.0);
});

it('sums the balance by account type up to and including the given date', function () {
    $account = Account::factory()->expense()->create();
    postJournalLine($account, ['posted_at' => today()->subDay()], ['dr' => 500, 'cr' => 0]);
    postJournalLine($account, ['posted_at' => today()], ['dr' => 300, 'cr' => 0]);

    expect($this->service->balanceOnByType(AccountType::Expenses, today()))->toBe(800.0);
});

it('returns the credit and debit totals before the given date', function () {
    $account = Account::factory()->create();
    postJournalLine($account, ['posted_at' => today()->subDay()], ['dr' => 500, 'cr' => 200]);
    postJournalLine($account, ['posted_at' => today()], ['dr' => 999, 'cr' => 999]);

    $info = $this->service->balanceInfoByDate($account->id, today());

    expect($info->total_debit)->toBe(500.0)
        ->and($info->total_credit)->toBe(200.0);
});

it('includes only expense entries with a nonzero movement in the expense report', function () {
    $expenseAccount = Account::factory()->expense()->create();
    postJournalLine($expenseAccount, ['head' => 'journal', 'posted_at' => today()], ['dr' => 100, 'cr' => 0]);
    postJournalLine($expenseAccount, ['head' => 'journal', 'posted_at' => today()], ['dr' => 0, 'cr' => 0]);

    $expenses = $this->service->getExpenses(today()->startOfDay(), today()->endOfDay());

    expect($expenses)->toHaveCount(1);
});

it('filters the expense report by account name', function () {
    $match = Account::factory()->expense()->create(['name' => 'Rent Expense']);
    $other = Account::factory()->expense()->create(['name' => 'Utility Expense']);
    postJournalLine($match, ['head' => 'journal', 'posted_at' => today()], ['dr' => 100, 'cr' => 0]);
    postJournalLine($other, ['head' => 'journal', 'posted_at' => today()], ['dr' => 100, 'cr' => 0]);

    $expenses = $this->service->getExpenses(today()->startOfDay(), today()->endOfDay(), 'Rent');

    expect($expenses)->toHaveCount(1)
        ->and($expenses->first()->name)->toBe('Rent Expense');
});

it('includes commission entries tagged with the paying agent name', function () {
    $expenseAccount = Account::factory()->expense()->create();
    $agent = Account::factory()->agent()->create(['expense_account' => $expenseAccount->id, 'name' => 'Agent Smith']);

    $journal = Journal::factory()->create(['posted_at' => today()]);
    JournalDetail::factory()->create([
        'journal_id' => $journal->id,
        'account_id' => $expenseAccount->id,
        'dr' => 150,
        'cr' => 0,
    ]);
    JournalDetail::factory()->create([
        'journal_id' => $journal->id,
        'account_id' => $agent->id,
        'dr' => 0,
        'cr' => 150,
    ]);

    $expenses = $this->service->getExpenses(today()->startOfDay(), today()->endOfDay());

    $commissionRow = $expenses->firstWhere('account_id', $expenseAccount->id);
    expect($commissionRow)->not->toBeNull()
        ->and($commissionRow->detail)->toContain('Agent: Agent Smith');
});

it('includes partnership expense entries in the report', function () {
    $expenseAccount = Account::factory()->expense()->create();
    Account::factory()->partner()->create(['expense_account' => $expenseAccount->id]);
    postJournalLine($expenseAccount, ['head' => 'journal', 'posted_at' => today()], ['dr' => 75, 'cr' => 0]);

    $expenses = $this->service->getExpenses(today()->startOfDay(), today()->endOfDay());

    expect($expenses->firstWhere('account_id', $expenseAccount->id))->not->toBeNull();
});

it('calculates opening and closing balances for bank accounts', function () {
    $bank = Account::factory()->create(['type' => AccountType::Bank]);
    postJournalLine($bank, ['posted_at' => today()->subDays(5)], ['dr' => 1000, 'cr' => 0]);
    postJournalLine($bank, ['posted_at' => today()], ['dr' => 200, 'cr' => 50]);

    $summary = $this->service->bankBookSummary(today());

    $row = $summary['rows']->firstWhere('account_id', $bank->id);
    expect($row->opening_balance)->toBe(1000.0)
        ->and($row->debit)->toBe(200.0)
        ->and($row->credit)->toBe(50.0)
        ->and($row->closing_balance)->toBe(1150.0);
});

it('reports a zero movement for bank accounts with no transactions on or after the date', function () {
    $bank = Account::factory()->create(['type' => AccountType::Bank]);
    postJournalLine($bank, ['posted_at' => today()->subDays(5)], ['dr' => 1000, 'cr' => 0]);

    $summary = $this->service->bankBookSummary(today());

    $row = $summary['rows']->firstWhere('account_id', $bank->id);
    expect($row->debit)->toBe(0)
        ->and($row->credit)->toBe(0)
        ->and($row->closing_balance)->toBe(1000.0);
});
