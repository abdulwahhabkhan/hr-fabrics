<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

it('renders the income statement index page with expected props', function (): void {
    $user = $this->getAdmin();

    $start = now()->subDay()->toDateString();
    $end = now()->toDateString();

    $response = $this->actingAs($user)->get(route('accounts.income-statement', [
        'start_date' => $start,
        'end_date' => $end,
    ]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Accounts/IncomeStatementReport')
        // filters and normalized dates
        ->has('filters')
        ->where('filters.start_date', $start)
        ->where('filters.end_date', $end)
        // detail URL should be generated with same dates
        ->where('detail_url', route('accounts.income-statement.detail', [
            'start_date' => $start,
            'end_date' => $end,
        ]))
        // high-level props should exist (values may be empty/zero)
        ->has('sales')
        ->has('purchases')
        ->has('expenses')
        ->has('other_incomes')
        ->has('net_profit')
        ->has('gross_profit')
        ->has('total_expenses')
        ->has('total_other_income')
        ->has('configs')
    );
});

it('renders the income statement detail page with expected props', function (): void {

    $user = $this->getAdmin();

    $response = $this->actingAs($user)->get(route('accounts.income-statement.detail', [
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->toDateString(),
    ]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Accounts/IncomeStatementReportDetail')
        ->has('filters')
        ->has('purchases')
        ->has('sales')
        /* ->where('gross_profit')
         ->where('total_sales')
         ->where('total_cost')*/
        ->has('inventory')
    );
});
