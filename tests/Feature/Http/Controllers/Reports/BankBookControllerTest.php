<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Inertia\Testing\AssertableInertia as Assert;

it('loads the bank book report page', function () {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->bank()->create();
    Journal::factory()
        ->hasTransactions(1, ['account_id' => $account->id, 'dr' => 1000, 'cr' => 50])
        ->create();
    $this->attachPermissions($user, 'reports.account-report.bank-book');

    // Act
    $response = $this->actingAs($user)->get(route('reports.account-report.bank-book'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Accounts/BankReport')
        ->has('filters.start_date')
        ->has('rows')
        ->has('rows.0.name')
        ->has('rows.0.debit')
        ->has('rows.0.total_dr')
        ->has('rows.0.total_cr')
        ->has('rows.0.credit')
        ->has('rows.0.opening_balance')
        ->has('rows.0.closing_balance')
        ->has('totals', fn (Assert $page) => $page
            ->has('opening_balance')
            ->has('debit')
            ->has('credit')
            ->has('closing_balance')
        )
    );
});
