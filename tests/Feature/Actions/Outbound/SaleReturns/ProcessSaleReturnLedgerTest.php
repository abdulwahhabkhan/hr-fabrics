<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleReturns\ProcessSaleReturnLedger;
use App\Enums\PaymentMode;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\SalesReturn;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->customer = Account::factory()->customer()->create();
});

test('credit return credits the customer only', function () {
    $return = SalesReturn::factory()->closed()->create([
        'customer_id' => $this->customer->id,
        'payment_mode' => PaymentMode::Credit->value,
        'total_amount' => 1000,
    ]);

    resolve(ProcessSaleReturnLedger::class)->handle($return, $this->user);

    $this->verifyJournal($return->id, $this->customer->id, 0, 1000, $return->journalDetail());
    assertDatabaseCount(Journal::class, 1);
    assertDatabaseCount(JournalDetail::class, 1);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $return->getMorphClass(),
        'loggable_id' => $return->id,
    ]);
});

test('cash return pays the customer out of the cash account', function () {
    $cash = $this->creatCashAccount();
    $return = SalesReturn::factory()->closed()->create([
        'customer_id' => $this->customer->id,
        'payment_mode' => PaymentMode::Cash->value,
        'total_amount' => 1000,
    ]);

    resolve(ProcessSaleReturnLedger::class)->handle($return, $this->user);

    assertDatabaseCount(JournalDetail::class, 3);
    $this->verifyJournalDetail($this->customer->id, 1000, 0);
    $this->verifyJournalDetail($this->customer->id, 0, 1000);
    $this->verifyJournalDetail($cash->id, 1000, 0);
});

test('agent commission is reversed against the agent expense account', function () {
    $expense = Account::factory()->expense()->create();
    $agent = Account::factory()->agent()->create(['expense_account' => $expense->id]);
    $return = SalesReturn::factory()->closed()->create([
        'customer_id' => $this->customer->id,
        'payment_mode' => PaymentMode::Credit->value,
        'total_amount' => 1000,
        'agent_id' => $agent->id,
        'commission' => 50,
    ]);

    resolve(ProcessSaleReturnLedger::class)->handle($return, $this->user);

    assertDatabaseCount(JournalDetail::class, 3);
    $this->verifyJournalDetail($agent->id, 0, 50);
    $this->verifyJournalDetail($expense->id, 50, 0);
});

test('agent commission only debits the agent when no expense account is set', function () {
    $agent = Account::factory()->agent()->create(['expense_account' => 0]);
    $return = SalesReturn::factory()->closed()->create([
        'customer_id' => $this->customer->id,
        'payment_mode' => PaymentMode::Credit->value,
        'total_amount' => 1000,
        'agent_id' => $agent->id,
        'commission' => 50,
    ]);

    resolve(ProcessSaleReturnLedger::class)->handle($return, $this->user);

    assertDatabaseCount(JournalDetail::class, 2);
    $this->verifyJournalDetail($agent->id, 0, 50);
});

test('agent without commission gets no ledger entry', function () {
    $agent = Account::factory()->agent()->create();
    $return = SalesReturn::factory()->closed()->create([
        'customer_id' => $this->customer->id,
        'payment_mode' => PaymentMode::Credit->value,
        'total_amount' => 1000,
        'agent_id' => $agent->id,
        'commission' => 0,
    ]);

    resolve(ProcessSaleReturnLedger::class)->handle($return, $this->user);

    assertDatabaseCount(JournalDetail::class, 1);
    assertDatabaseHas(JournalDetail::class, ['account_id' => $this->customer->id, 'cr' => 1000]);
});
