<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
    PermissionFacade::fake(['accounts.ledgers.*' => true]);
});

test('ledger index page is displayed', function () {
    $response = $this->get(route('accounts.ledgers.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Ledgers/LedgerIndex')
        ->has('accounts')
        ->has('date.start_date')
        ->has('date.end_date')
    );
});

test('ledger show page displays correct previous balance', function () {
    // Arrange
    $account = Account::factory()->create(['name' => 'Test Account']);

    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();

    // 1. Transaction BEFORE start date (Previous Balance)
    $oldJournal = Journal::factory()->create([
        'posted_at' => $startDate->copy()->subDays(5),
    ]);
    JournalDetail::factory()->create([
        'journal_id' => $oldJournal->id,
        'account_id' => $account->id,
        'dr' => 1000,
        'cr' => 0,
    ]);

    // 2. Transaction WITHIN date range
    $currentJournal = Journal::factory()->create([
        'posted_at' => $startDate->copy()->addDays(2),
    ]);
    JournalDetail::factory()->create([
        'journal_id' => $currentJournal->id,
        'account_id' => $account->id,
        'dr' => 500,
        'cr' => 0,
    ]);

    // 3. Another transaction WITHIN range (Credit)
    JournalDetail::factory()->create([
        'journal_id' => $currentJournal->id,
        'account_id' => $account->id,
        'dr' => 0,
        'cr' => 200,
    ]);

    // Action
    $response = $this->get(route('accounts.ledgers.show', [
        'account' => $account->id,
        'start_date' => $startDate->format('d-M-Y'),
        'end_date' => $endDate->format('d-M-Y'),
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Ledgers/LedgerView')
        ->where('account.id', $account->id)
        ->where('start_balance', 1000) // Previous balance from before $startDate
        ->where('net_balance', 1300)   // 1000 + 500 - 200
        ->has('ledger', 2)               // Only transactions within range
    );
});

test('ledger show page handles account with no transactions', function () {
    $account = Account::factory()->create(['name' => 'Empty Account']);
    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();

    $response = $this->get(route('accounts.ledgers.show', [
        'account' => $account->id,
        'start_date' => $startDate->format('d-M-Y'),
        'end_date' => $endDate->format('d-M-Y'),
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Ledgers/LedgerView')
        ->where('start_balance', 0)
        ->where('net_balance', 0)
        ->has('ledger', 0)
    );
});

test('ledger show page handles account with only transactions within range', function () {
    $account = Account::factory()->create();
    $startDate = now()->startOfMonth();

    $journal = Journal::factory()->create(['posted_at' => $startDate->copy()->addDay()]);
    JournalDetail::factory()->create([
        'journal_id' => $journal->id,
        'account_id' => $account->id,
        'dr' => 500,
        'cr' => 0,
    ]);

    $response = $this->get(route('accounts.ledgers.show', [
        'account' => $account->id,
        'start_date' => $startDate->format('d-M-Y'),
        'end_date' => now()->addDay()->format('d-M-Y'),
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->where('start_balance', 0)
        ->where('net_balance', 500)
        ->has('ledger', 1)
    );
});
