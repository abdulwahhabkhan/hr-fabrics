<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
it('shows customer balance report without filters', function (): void {
    $this->actingAs($this->getAdmin());

    $response = $this->get(route('reports.customers.balance'));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Reports/Customers/BalanceReport')
                ->has('suspended')
                ->has('limit')
                ->has('rows')
                ->has('total_balance');
        });
});

it('returns filtered props for suspended and limit parameters', function (): void {
    $this->actingAs($this->getAdmin());

    $params = ['suspended' => 1, 'limit' => 2];

    $response = $this->get(route('reports.customers.balance', $params));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($params): void {
            $page->component('Reports/Customers/BalanceReport')
                ->where('suspended', $params['suspended'])
                ->where('limit', $params['limit'])
                ->has('rows')
                ->has('total_balance');
        });
});
