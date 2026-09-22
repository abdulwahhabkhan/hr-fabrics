<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs($this->getAdmin());
    $this->cashAccount = Account::factory()->create(['type' => 'assets', 'name' => 'Cash Account']);
    PermissionFacade::fake(['*' => true]);
});

test('defaults start_date and end_date to today when none given', function () {
    // Action
    $response = $this->get(route('reports.in.out.transactions.summary'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/InOutTransactionsSummary')
        ->where('filters.start_date', today()->toDateString())
        ->where('filters.end_date', today()->toDateString())
    );
});

test('accepts explicit start_date and end_date filters', function () {
    // Action
    $response = $this->get(route('reports.in.out.transactions.summary', [
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-15',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.start_date', '2026-07-01')
        ->where('filters.end_date', '2026-07-15')
    );
});

test('defaults a missing end_date to today independently of start_date', function () {
    // Action
    $response = $this->get(route('reports.in.out.transactions.summary', [
        'start_date' => '2026-07-01',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.start_date', '2026-07-01')
        ->where('filters.end_date', today()->toDateString())
    );
});

test('reports the same totals after the TransactionBook query-builder refactor', function () {
    // Arrange: one debit and one credit leg per category, inside the filtered range.
    $advances = Account::factory()->advances()->create();
    $customer = Account::factory()->create(['type' => 'customer']);
    $drawings = Account::factory()->drawings()->create();
    $otherReceivable = Account::factory()->otherReceivable()->create();
    $payable = Account::factory()->payable()->create();
    $supplier = Account::factory()->create(['type' => 'supplier']);

    foreach ([$advances, $customer, $drawings, $otherReceivable, $payable, $supplier] as $account) {
        Journal::factory()
            ->hasTransactions(1, ['account_id' => $account->id, 'dr' => 500, 'cr' => 0])
            ->create(['head' => 'journal', 'posted_at' => '2026-07-10']);
        Journal::factory()
            ->hasTransactions(1, ['account_id' => $account->id, 'dr' => 0, 'cr' => 300])
            ->create(['head' => 'journal', 'posted_at' => '2026-07-11']);
    }

    Journal::factory()
        ->hasTransactions(1, ['account_id' => $this->cashAccount->id, 'dr' => 700, 'cr' => 0])
        ->create(['head' => 'sales', 'posted_at' => '2026-07-10']);

    // Action
    $response = $this->get(route('reports.in.out.transactions.summary', [
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]));

    // Assert: every category picked up both legs, matching the pre-refactor query shape.
    $response->assertInertia(fn (Assert $page) => $page
        ->where('total_advances.total_dr', 500)
        ->where('total_advances.total_cr', 300)
        ->where('total_customers.total_dr', 500)
        ->where('total_customers.total_cr', 300)
        ->where('total_drawings.total_dr', 500)
        ->where('total_drawings.total_cr', 300)
        ->where('total_others_receivables.total_dr', 500)
        ->where('total_others_receivables.total_cr', 300)
        ->where('total_payables.total_dr', 500)
        ->where('total_payables.total_cr', 300)
        ->where('total_suppliers.total_dr', 500)
        ->where('total_suppliers.total_cr', 300)
        ->where('total_cash_sales.total_dr', 700)
    );
});
