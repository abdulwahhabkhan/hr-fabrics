<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Services\JournalService;

beforeEach(function () {
    $this->service = new JournalService;
    $this->cashAccount = Account::factory()->cash()->create();
});

it('sums only cash entries posted before the start of the given day for the opening balance', function () {
    $before = Journal::factory()->create(['posted_at' => today()->subDays(2)]);
    JournalDetail::factory()->debit(1000)->create([
        'journal_id' => $before->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $onDay = Journal::factory()->create(['posted_at' => today()]);
    JournalDetail::factory()->debit(500)->create([
        'journal_id' => $onDay->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $balance = $this->service->getCashOpeningBalance(today());

    expect($balance)->toBe(1000);
});

it('excludes non-cash accounts from the opening balance', function () {
    $before = Journal::factory()->create(['posted_at' => today()->subDays(2)]);
    JournalDetail::factory()->debit(1000)->create([
        'journal_id' => $before->id,
        'account_id' => Account::factory()->create()->id,
    ]);

    $balance = $this->service->getCashOpeningBalance(today());

    expect($balance)->toBe(0);
});

it('sums cash entries posted through the end of the given day for the closing balance', function () {
    $onDay = Journal::factory()->create(['posted_at' => today()]);
    JournalDetail::factory()->debit(500)->create([
        'journal_id' => $onDay->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $after = Journal::factory()->create(['posted_at' => today()->addDay()]);
    JournalDetail::factory()->debit(300)->create([
        'journal_id' => $after->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $balance = $this->service->getCashClosingBalance(today());

    expect($balance)->toBe(500);
});

it('nets debits and credits for the closing balance', function () {
    $journal = Journal::factory()->create(['posted_at' => today()]);
    JournalDetail::factory()->debit(500)->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cashAccount->id,
    ]);
    JournalDetail::factory()->credit(200)->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $balance = $this->service->getCashClosingBalance(today());

    expect($balance)->toBe(300);
});

it('sums only cash sales entries within the given day', function () {
    $journal = Journal::factory()->create(['head' => 'sales', 'posted_at' => today()]);
    JournalDetail::factory()->debit(400)->create([
        'journal_id' => $journal->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $otherHead = Journal::factory()->create(['head' => 'admin', 'posted_at' => today()]);
    JournalDetail::factory()->debit(999)->create([
        'journal_id' => $otherHead->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $outsideDay = Journal::factory()->create(['head' => 'sales', 'posted_at' => today()->subDay()]);
    JournalDetail::factory()->debit(999)->create([
        'journal_id' => $outsideDay->id,
        'account_id' => $this->cashAccount->id,
    ]);

    $sales = $this->service->getCashSales(today());

    expect($sales)->toBe(400);
});
