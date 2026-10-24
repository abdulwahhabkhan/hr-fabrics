<?php

declare(strict_types=1);

use App\Enums\JournalHead;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
    $this->actingAs($this->getAdmin());
    $this->cashAccount = $this->creatCashAccount();
});

it('defaults the daily activity report to today', function (): void {
    $this->travelTo(Carbon::create(2025, 12, 14, 10, 0, 0));

    $this->get(route('reports.daily-activity'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Summary/DailyActivityReport')
            ->hasAll([
                'bank_details',
                'expenses',
                'expenses_total_expenses',
                'expenses_total_cr',
                'net_expenses',
                'sale_summary',
                'journal_rows',
                'opening_balance',
                'cash_sales',
                'closing_balance',
            ])
            ->where('filters.date', '14-Dec-2025')
        );
});

it('lists only journal head entries for the selected date without the cash side', function (): void {
    $account = Account::factory()->create();

    $journal = Journal::factory()->create(['head' => JournalHead::Journal, 'posted_at' => '2025-12-01']);
    JournalDetail::factory()->debit(500)->create(['journal_id' => $journal->id, 'account_id' => $account->id]);
    JournalDetail::factory()->credit(500)->create(['journal_id' => $journal->id, 'account_id' => $this->cashAccount->id]);

    $otherDay = Journal::factory()->create(['head' => JournalHead::Journal, 'posted_at' => '2025-12-02']);
    JournalDetail::factory()->debit(100)->create(['journal_id' => $otherDay->id, 'account_id' => $account->id]);

    $sale = Journal::factory()->create(['head' => JournalHead::Sales, 'posted_at' => '2025-12-01']);
    JournalDetail::factory()->debit(200)->create(['journal_id' => $sale->id, 'account_id' => $account->id]);

    $this->get(route('reports.daily-activity', ['date' => '2025-12-01']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.date', '01-Dec-2025')
            ->has('journal_rows', 1)
            ->where('journal_rows.0.account_id', $account->id)
            ->where('journal_rows.0.debit', fn ($debit): bool => (float) $debit === 500.0)
        );
});
