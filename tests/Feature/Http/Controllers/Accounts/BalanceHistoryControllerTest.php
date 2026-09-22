<?php

use App\Models\Accounts\Account;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('customer balance history page loaded', function () {
    // arrange
    $customers = Account::factory(3)->customer()->create();

    // action
    $response = $this->get(route('accounts.balance-history'));
    // assert
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Balance/HistoryIndex')
        ->has('customer')
        ->has('accounts')
        ->has('data')
        ->has('rows')
        ->has('net_balance')
    );

});

test('customer balance history of a customer', function () {
    // arrange
    $customers = Account::factory(3)->customer()->create();
    $customer = $customers->first();
    $filters = [
        'customer' => $customer,
    ];
    // action
    $response = $this->get(route('accounts.balance-history', $filters));
    // assert
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Balance/HistoryIndex')
        ->has('customer')
        ->has('accounts')
        ->has('data')
        ->has('rows')
        ->has('net_balance')
    );

});
