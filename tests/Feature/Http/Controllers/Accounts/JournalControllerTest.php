<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Enums\JournalHead;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Accounts\JournalLedger;
use App\Models\Action\Log;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

test('journal index page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->customer()->count(3)->create();

    // Act
    $response = $this->actingAs($user)->get(route('accounts.journals.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Journals/JournalIndex')
        ->has('vouchers')
        ->has('vouchers.links')
        ->has('vouchers.data')
        ->has('types')
        ->has('filters')
        ->has('canAdd')
        ->has('canAddSingle')
        ->has('canView')
    );
});

test('journal index returns applied filters', function () {
    // Arrange
    $user = $this->getAdmin();

    // Act
    $response = $this->actingAs($user)->get(route('accounts.journals.index', [
        'type' => 'sales',
        'reference_no' => 'JV-1',
        'unrelated' => 'ignored',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters', ['type' => 'sales', 'reference_no' => 'JV-1'])
    );
});

test('journal single entry page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->customer()->count(3)->create();

    // Act
    $response = $this->actingAs($user)->get(route('accounts.journals.single'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Journals/JournalSingleForm')
        ->has('accounts')
    );
});

test('journal add page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->customer()->count(3)->create();

    // Act
    $response = $this->actingAs($user)->get(route('accounts.journals.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Journals/JournalForm')
        ->has('accounts')
    );
});

test('journal view page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $accounts = Account::factory()->customer()->count(3)->create();
    $journal = Journal::factory()
        ->has(JournalDetail::factory()->count(2), 'transactions')
        ->create(['user_id' => $user->id]);

    // Act
    $response = $this->actingAs($user)->get(route('accounts.journals.show', $journal->id));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Journals/JournalView')
        ->has('transactions')
        ->has('amount')
        ->has('user')
        ->has('journal')
    );
});

test('store journal double entry validation', function () {
    // Arrange
    $user = $this->getAdmin();
    $fromAccount = Account::factory()->customer()->create();
    $data = [];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.store'), $data);

    // Assert
    $response->assertSessionHasErrors(['amount', 'from_account', 'to_account', 'date', 'detail']);

    $data = [
        'from_account' => $fromAccount,
        'to_account' => $fromAccount,
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.store'), $data);

    // Assert
    $response->assertSessionHasErrors(['amount', 'to_account', 'date', 'detail']);
});

test('store journal single debit entry', function () {
    // Arrange
    $user = $this->getAdmin();
    $fromAccount = Account::factory()->customer()->create();
    $cashAccount = Account::factory()->cash()->create();
    $date = Carbon::now()->format('Y-m-d');
    $amount = 10000;
    $data = [
        'account' => $fromAccount,
        'amount' => $amount,
        'date' => $date,
        'file' => [
            'file_name' => 'test.png',
            'file_thumbnail' => 'test_thumbnail.png',
        ],
        'type' => 'debit',
        'detail' => 'Cash received',
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.single-store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $this->assertDatabaseHas(Journal::class, [
        'posted_at' => $date,
        'head' => JournalHead::Journal->value,
        'detail' => 'Cash received',
    ]);

    $this->assertDatabaseCount(JournalDetail::class, 2);

    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $fromAccount->id,
        'cr' => 0,
        'dr' => $amount,
    ]);
    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $cashAccount->id,
        'cr' => $amount,
        'dr' => 0,
    ]);

    $this->assertDatabaseCount(Log::class, 1);
});

test('store journal single credit entry', function () {
    // Arrange
    $user = $this->getAdmin();
    $fromAccount = Account::factory()->customer()->create();
    $cashAccount = Account::factory()->cash()->create();
    $date = Carbon::now()->format('Y-m-d');
    $amount = 10000;
    $data = [
        'account' => $fromAccount,
        'amount' => $amount,
        'date' => $date,
        'file' => [
            'file_name' => 'test.png',
            'file_thumbnail' => 'test_thumbnail.png',
        ],
        'type' => 'credit',
        'detail' => 'Cash received',
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.single-store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $this->assertDatabaseHas(Journal::class, [
        'posted_at' => $date,
        'head' => JournalHead::Journal->value,
        'detail' => 'Cash received',
    ]);

    $this->assertDatabaseCount(JournalDetail::class, 2);

    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $fromAccount->id,
        'cr' => $amount,
        'dr' => 0,
    ]);

    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $cashAccount->id,
        'cr' => 0,
        'dr' => $amount,
    ]);

    $this->assertDatabaseCount(Log::class, 1);
});

test('store journal double entry', function () {
    // Arrange
    $user = $this->getAdmin();
    $fromAccount = Account::factory()->customer()->create();
    $toAccount = Account::factory()->create(['name' => 'Cash']);
    $date = Carbon::now()->format('Y-m-d');
    $amount = 10000;
    $data = [
        'from_account' => $fromAccount,
        'to_account' => $toAccount,
        'amount' => $amount,
        'date' => $date,
        'file' => [
            'file_name' => 'test.png',
            'file_thumbnail' => 'test_thumbnail.png',
        ],
        'detail' => 'Cash received',
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $this->assertDatabaseHas(Journal::class, [
        'posted_at' => $date,
        'head' => JournalHead::Journal->value,
        'detail' => 'Cash received',
    ]);

    $this->assertDatabaseCount(JournalDetail::class, 2);

    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $fromAccount->id,
        'dr' => 0,
        'cr' => $amount,
    ]);

    $this->assertDatabaseHas(JournalDetail::class, [
        'account_id' => $toAccount->id,
        'cr' => 0,
        'dr' => $amount,
    ]);

    $this->assertDatabaseCount(Log::class, 1);
});

test('get account balance', function () {
    // arrange
    $this->user = $this->getAdmin();
    $account = Account::factory()->customer()->create();
    JournalDetail::factory(3)
        ->create([
            'account_id' => $account->id,
            'dr' => 500,
            'cr' => 300,
        ]);

    $total_debit = 500 * 3;
    $total_credit = 300 * 3;
    // action
    $response = $this->actingAs($this->user)
        ->get(route('accounts.accounts.balance', ['account' => $account->id]));

    // assert
    $response->assertOk();
    $response->assertJson([
        'balance' => $total_debit - $total_credit,
        'debit' => $total_debit,
        'credit' => $total_credit,
    ]);
});

test('store journal single entry keeps karachi date when posted as utc datetime', function () {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->customer()->create();
    Account::factory()->cash()->create();
    $data = [
        'account' => $account,
        'amount' => 500,
        'date' => '2026-09-23T20:30:00.000Z',
        'type' => 'debit',
        'detail' => 'Early morning entry',
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.single-store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas(Journal::class, [
        'posted_at' => '2026-09-24',
        'detail' => 'Early morning entry',
    ]);
});

test('store journal double entry keeps karachi date when posted as utc datetime', function () {
    // Arrange
    $user = $this->getAdmin();
    $fromAccount = Account::factory()->customer()->create();
    $toAccount = Account::factory()->cash()->create();
    $data = [
        'from_account' => $fromAccount,
        'to_account' => $toAccount,
        'amount' => 500,
        'date' => '2026-09-23T20:30:00.000Z',
        'detail' => 'Early morning entry',
    ];

    // Act
    $response = $this->actingAs($user)->post(route('accounts.journals.store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas(Journal::class, [
        'posted_at' => '2026-09-24',
        'detail' => 'Early morning entry',
    ]);
});

test('journal index and view return posted date without time', function () {
    // Arrange
    $user = $this->getAdmin();
    $journal = Journal::factory()
        ->has(JournalDetail::factory()->count(2), 'transactions')
        ->create(['user_id' => $user->id, 'posted_at' => '2026-09-24']);

    // Act
    $indexResponse = $this->actingAs($user)->get(route('accounts.journals.index'));
    $showResponse = $this->actingAs($user)->get(route('accounts.journals.show', $journal->id));

    // Assert
    $indexResponse->assertInertia(fn (Assert $page) => $page
        ->where('vouchers.data.0.date', '2026-09-24')
    );
    $showResponse->assertInertia(fn (Assert $page) => $page
        ->where('journal.posted_at', '2026-09-24')
    );
});

test('journal delete is not available', function () {
    // Arrange
    $user = $this->getAdmin();
    $journal = Journal::factory()
        ->has(JournalDetail::factory()->count(2), 'transactions')
        ->create(['user_id' => $user->id]);

    // Act
    $response = $this->actingAs($user)->delete("/accounts/journals/{$journal->id}");

    // Assert
    $response->assertStatus(405);
    $this->assertNotSoftDeleted($journal);
    expect(Route::has('accounts.journals.destroy'))->toBeFalse();
});

test('soft deleted journals are excluded from balances and ledger views', function () {
    // Arrange
    $account = Account::factory()->customer()->create();
    $kept = Journal::factory()->create();
    $removed = Journal::factory()->create();
    JournalDetail::factory()->create(['journal_id' => $kept->id, 'account_id' => $account->id, 'dr' => 500, 'cr' => 0]);
    JournalDetail::factory()->create(['journal_id' => $removed->id, 'account_id' => $account->id, 'dr' => 300, 'cr' => 0]);

    // Act
    $removed->transactions()->delete();
    $removed->delete();

    // Assert
    expect(JournalLedger::query()->where('account_id', $account->id)->sum('dr'))->toEqual(500)
        ->and(DB::table('account_summary')->where('id', $account->id)->value('total_dr'))->toEqual(500)
        ->and(resolve(AccountService::class)->getAccountBalance($account->id))->toBe(500)
        ->and(Journal::withTrashed()->count())->toBe(2);
});

test('new journal reference number does not reuse a soft deleted journal id', function () {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->customer()->create();
    $cash = Account::factory()->cash()->create();
    $journal = Journal::factory()->create();
    $journal->delete();

    // Act
    $this->actingAs($user)->post(route('accounts.journals.single-store'), [
        'account' => $account, 'amount' => 100, 'date' => '2026-11-08', 'type' => 'debit', 'detail' => 'Fix',
    ])->assertSessionHasNoErrors();

    // Assert
    $this->assertDatabaseHas(Journal::class, ['reference_no' => 'JV-'.str((string) ($journal->id + 1))->padLeft(5, '0')]);
});
