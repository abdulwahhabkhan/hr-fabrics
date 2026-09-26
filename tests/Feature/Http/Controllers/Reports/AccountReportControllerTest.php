<?php

use App\Facades\Permission as PermissionFacade;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\City;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    PermissionFacade::fake(['*' => true]);
});

/**
 * Post a single journal line for the given account.
 *
 * @param  array<string, mixed>  $detail
 */
function postLedgerLine(Account $account, int $dr, int $cr, string $postedAt, array $detail = []): void
{
    Journal::factory()
        ->hasTransactions(1, ['account_id' => $account->id, 'dr' => $dr, 'cr' => $cr, ...$detail])
        ->create(['head' => 'journal', 'posted_at' => $postedAt]);
}

/**
 * @return array<string, string>
 */
function addressIn(string $city): array
{
    return ['address' => $city.' street', 'city' => $city, 'region' => 'Region', 'country' => 'Pakistan'];
}

describe('index', function () {
    it('forbids users without the report permission', function () {
        PermissionFacade::fake(['reports.account-report.*' => false]);

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.index'));

        $response->assertForbidden();
    });

    it('defaults the date filter to today', function () {
        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Accounts/AccountReport')
            ->where('filters.date', today()->toDateString())
        );
    });

    it('lists account balances posted on or before the date and skips settled accounts', function () {
        $open = Account::factory()->customer()->create(['name' => 'Open Account']);
        $settled = Account::factory()->customer()->create(['name' => 'Settled Account']);
        postLedgerLine($open, 1000, 0, '2026-07-10');
        postLedgerLine($open, 0, 400, '2026-07-15');
        postLedgerLine($open, 0, 100, '2026-07-16');
        postLedgerLine($settled, 500, 0, '2026-07-10');
        postLedgerLine($settled, 0, 500, '2026-07-12');

        $response = $this->actingAs($this->getAdmin())
            ->get(route('reports.account-report.index', ['date' => '2026-07-15']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.name', 'Open Account')
            ->where('rows.data.0.type', 'Customer')
            ->where('rows.data.0.total_dr', 1000)
            ->where('rows.data.0.total_cr', 400)
            ->where('rows.data.0.balance', 600)
        );
    });

    it('filters rows by type', function () {
        $customer = Account::factory()->customer()->create();
        $supplier = Account::factory()->supplier()->create(['name' => 'Supplier Account']);
        postLedgerLine($customer, 1000, 0, '2026-07-10');
        postLedgerLine($supplier, 0, 700, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())
            ->get(route('reports.account-report.index', ['date' => '2026-07-31', 'type' => 'supplier']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.name', 'Supplier Account')
            ->where('rows.data.0.balance', -700)
        );
    });
});

describe('receivables', function () {
    it('forbids users without the report permission', function () {
        PermissionFacade::fake(['reports.account-report.*' => false]);

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.receivables'));

        $response->assertForbidden();
    });

    it('lists only customers with an open balance and totals them', function () {
        $receivable = Account::factory()->customer()->create();
        $advance = Account::factory()->customer()->create();
        $settled = Account::factory()->customer()->create();
        $supplier = Account::factory()->supplier()->create();
        postLedgerLine($receivable, 1000, 0, '2026-07-10');
        postLedgerLine($advance, 0, 300, '2026-07-10');
        postLedgerLine($settled, 500, 0, '2026-07-10');
        postLedgerLine($settled, 0, 500, '2026-07-11');
        postLedgerLine($supplier, 900, 0, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.receivables'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Receivable/ReceivableReport')
            ->has('rows', 2)
            ->where('rows.0.account_id', $advance->id)
            ->where('rows.1.account_id', $receivable->id)
            ->where('total_balance', 700)
        );
    });

    it('filters customers by balance direction', function (string $filterType, string $expected) {
        $accounts = [
            'receivable' => Account::factory()->customer()->create(),
            'advance' => Account::factory()->customer()->create(),
        ];
        postLedgerLine($accounts['receivable'], 1000, 0, '2026-07-10');
        postLedgerLine($accounts['advance'], 0, 300, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())
            ->get(route('reports.account-report.receivables', ['filter_type' => $filterType]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.account_id', $accounts[$expected]->id)
        );
    })->with([
        'receivable only' => ['receivable', 'receivable'],
        'advance only' => ['advance', 'advance'],
    ]);

    it('filters by a single customer', function () {
        $wanted = Account::factory()->customer()->create();
        $other = Account::factory()->customer()->create();
        postLedgerLine($wanted, 1000, 0, '2026-07-10');
        postLedgerLine($other, 2000, 0, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())
            ->get(route('reports.account-report.receivables', ['filter_customers' => ['id' => $wanted->id]]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.account_id', $wanted->id)
            ->where('total_balance', 1000)
        );
    });

    it('excludes lines posted after the posted date filter', function () {
        $customer = Account::factory()->customer()->create();
        postLedgerLine($customer, 1000, 0, '2026-07-10');
        postLedgerLine($customer, 500, 0, '2026-07-20');

        $response = $this->actingAs($this->getAdmin())
            ->get(route('reports.account-report.receivables', ['filter_posted_date' => '2026-07-15']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('filters.filter_post_date')
            ->where('total_balance', 1000)
        );
    });
});

describe('receivables by city', function () {
    it('forbids users without the report permission', function () {
        PermissionFacade::fake(['reports.account-report.*' => false]);

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.city-by-receivables'));

        $response->assertForbidden();
    });

    it('groups open customer balances by the selected cities', function () {
        City::factory()->create(['name' => 'Karachi']);
        $karachiFirst = Account::factory()->customer()->create(['address' => addressIn('Karachi')]);
        $karachiSecond = Account::factory()->customer()->create(['address' => addressIn('Karachi')]);
        $karachiSettled = Account::factory()->customer()->create(['address' => addressIn('Karachi')]);
        $lahore = Account::factory()->customer()->create(['address' => addressIn('Lahore')]);
        $karachiSupplier = Account::factory()->supplier()->create(['address' => addressIn('Karachi')]);
        postLedgerLine($karachiFirst, 1000, 0, '2026-07-10');
        postLedgerLine($karachiSecond, 800, 0, '2026-07-10');
        postLedgerLine($karachiSecond, 0, 300, '2026-07-11');
        postLedgerLine($karachiSettled, 400, 0, '2026-07-10');
        postLedgerLine($karachiSettled, 0, 400, '2026-07-11');
        postLedgerLine($lahore, 5000, 0, '2026-07-10');
        postLedgerLine($karachiSupplier, 900, 0, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.city-by-receivables', [
            'filter_cities' => [['name' => 'Karachi']],
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Receivable/ReceivableReportByCity')
            ->has('rows', 1)
            ->where('rows.0.city', 'Karachi')
            ->has('rows.0.customers', 2)
            ->where('rows.0.city_total', 1500)
            ->where('total_balance', 1500)
            ->where('cities', [['name' => 'Karachi']])
        );
    });

    it('returns no rows when no city is selected', function () {
        $customer = Account::factory()->customer()->create(['address' => addressIn('Karachi')]);
        postLedgerLine($customer, 1000, 0, '2026-07-10');

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.city-by-receivables'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('rows', 0)
            ->where('total_balance', 0)
        );
    });

    it('reports the unpaid balance of debits older than 150 days', function () {
        $customer = Account::factory()->customer()->create(['address' => addressIn('Karachi')]);
        postLedgerLine($customer, 1000, 0, now()->subDays(200)->toDateString(), ['created_at' => now()->subDays(200)]);
        postLedgerLine($customer, 500, 0, now()->subDays(10)->toDateString(), ['created_at' => now()->subDays(10)]);
        postLedgerLine($customer, 0, 300, now()->subDays(5)->toDateString(), ['created_at' => now()->subDays(5)]);

        $response = $this->actingAs($this->getAdmin())->get(route('reports.account-report.city-by-receivables', [
            'filter_cities' => [['name' => 'Karachi']],
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.customers.0.balance_150_days', '700')
        );
    });
});
