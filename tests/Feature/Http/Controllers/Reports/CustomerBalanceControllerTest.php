<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    PermissionFacade::fake(['*' => true]);
});

it('reports the stored credit limit with the suspended flag so the page can show a zero limit', function () {
    $suspended = Account::factory()->customer()->create(['suspended' => true, 'credit' => true, 'limit' => 50000]);
    $active = Account::factory()->customer()->create(['suspended' => false, 'credit' => true, 'limit' => 30000]);
    Journal::factory()
        ->hasTransactions(1, ['account_id' => $suspended->id, 'dr' => 1000, 'cr' => 0])
        ->create();
    Journal::factory()
        ->hasTransactions(1, ['account_id' => $active->id, 'dr' => 2000, 'cr' => 0])
        ->create();

    $response = $this->actingAs($this->getAdmin())->get(route('reports.customers.balance'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Customers/BalanceReport')
        ->has('rows', 2)
        ->where('rows.0.id', $suspended->id)
        ->where('rows.0.suspended', true)
        ->where('rows.0.limit', 50000)
        ->where('rows.1.id', $active->id)
        ->where('rows.1.suspended', false)
        ->where('rows.1.limit', 30000)
    );
});

it('filters customers by credit limit, treating a suspended customer as a zero limit', function (int $filter, array $expected) {
    $customers = [
        'suspended with old limit' => Account::factory()->customer()->create(['suspended' => true, 'credit' => true, 'limit' => 50000]),
        'suspended without limit' => Account::factory()->customer()->create(['suspended' => true, 'credit' => false, 'limit' => null]),
        'active limited' => Account::factory()->customer()->create(['suspended' => false, 'credit' => true, 'limit' => 30000]),
        'active zero limit' => Account::factory()->customer()->create(['suspended' => false, 'credit' => true, 'limit' => 0]),
        'active empty limit' => Account::factory()->customer()->create(['suspended' => false, 'credit' => true, 'limit' => null]),
    ];
    foreach ($customers as $customer) {
        Journal::factory()
            ->hasTransactions(1, ['account_id' => $customer->id, 'dr' => 1000, 'cr' => 0])
            ->create();
    }

    $response = $this->actingAs($this->getAdmin())->get(route('reports.customers.balance', ['limit' => $filter]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('rows', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
            === collect($expected)->map(fn (string $key) => $customers[$key]->id)->sort()->values()->all())
    );
})->with([
    'limited' => [1, ['suspended with old limit', 'suspended without limit', 'active limited']],
    'unlimited' => [2, ['active zero limit', 'active empty limit']],
]);
