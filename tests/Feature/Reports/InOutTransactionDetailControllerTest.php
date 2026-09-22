<?php

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Inertia\Testing\AssertableInertia as Assert;

function seedLeg(Account $account, string $head, string $date, int $dr, int $cr): void
{
    Journal::factory()
        ->hasTransactions(1, ['account_id' => $account->id, 'dr' => $dr, 'cr' => $cr])
        ->create(['head' => $head, 'posted_at' => $date]);
}

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->cashAccount = Account::factory()->create(['type' => 'bank', 'name' => 'Cash Account']);
    $this->attachPermissions($user, 'reports.in.out.transactions.detail');
});

test('renders the detail page with filters and a label', function () {
    // Action
    $response = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances',
        'side' => 'dr',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/InOutTransactionDetail')
        ->where('filters.category', 'advances')
        ->where('filters.side', 'dr')
        ->where('filters.start_date', '2026-07-01')
        ->where('filters.end_date', '2026-07-31')
        ->where('label', 'Advances (If Debited)')
    );
});

test('side=dr returns only debit rows and side=cr returns only credit rows', function () {
    // Arrange
    $account = Account::factory()->advances()->create();
    seedLeg($account, 'journal', '2026-07-10', 500, 0);
    seedLeg($account, 'journal', '2026-07-11', 0, 300);

    // Action
    $debitResponse = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances', 'side' => 'dr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));
    $creditResponse = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances', 'side' => 'cr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));

    // Assert
    $debitResponse->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)
        ->where('rows.0.dr', 500)
        ->where('total', 500)
    );
    $creditResponse->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)
        ->where('rows.0.cr', 300)
        ->where('total', 300)
    );
});

test('rows outside the date range are excluded', function () {
    // Arrange
    $account = Account::factory()->advances()->create();
    seedLeg($account, 'journal', '2026-06-30', 500, 0);
    seedLeg($account, 'journal', '2026-08-01', 500, 0);
    seedLeg($account, 'journal', '2026-07-15', 500, 0);

    // Action
    $response = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances', 'side' => 'dr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)
        ->where('total', 500)
    );
});

test('category scoping matches the same head/account rules as the summary', function () {
    // Arrange
    $advancesAccount = Account::factory()->advances()->create();
    seedLeg($advancesAccount, 'journal', '2026-07-10', 500, 0); // matches
    seedLeg($advancesAccount, 'sales', '2026-07-10', 500, 0);   // wrong head, excluded

    seedLeg($this->cashAccount, 'sales', '2026-07-10', 700, 0);  // matches
    seedLeg($this->cashAccount, 'journal', '2026-07-10', 700, 0); // wrong head, excluded

    // Action
    $advancesResponse = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances', 'side' => 'dr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));
    $cashSaleResponse = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'cash_sale', 'side' => 'dr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));

    // Assert
    $advancesResponse->assertInertia(fn (Assert $page) => $page->has('rows', 1)->where('total', 500));
    $cashSaleResponse->assertInertia(fn (Assert $page) => $page->has('rows', 1)->where('total', 700));
});

test('cross-checks the detail total against the matching summary figure',
    function (string $category, string $side, string $summaryProp) {
        $this->fakeHavePermission();
        // Arrange: two legs per account (500 debit, 300 credit) so both sides produce a real number.
        $account = match ($category) {
            'cash_sale' => $this->cashAccount,
            'advances' => Account::factory()->advances()->create(),
            'charity' => Account::factory()->charity()->create(),
            'customer' => Account::factory()->create(['type' => 'customer']),
            'drawings' => Account::factory()->drawings()->create(),
            'other_receivable' => Account::factory()->otherReceivable()->create(),
            'payable' => Account::factory()->payable()->create(),
            'supplier' => Account::factory()->create(['type' => 'supplier']),
        };
        $head = $category === 'cash_sale' ? 'sales' : 'journal';
        seedLeg($account, $head, '2026-07-10', 500, 0);
        seedLeg($account, $head, '2026-07-11', 0, 300);

        // Action
        $summary = $this->get(route('reports.in.out.transactions.summary', [
            'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
        ]));
        $detail = $this->get(route('reports.in.out.transactions.detail', [
            'category' => $category, 'side' => $side, 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
        ]));

        // Assert
        $summaryValue = null;
        $summary->assertInertia(function (Assert $page) use ($summaryProp, &$summaryValue) {
            $page->where($summaryProp, function ($value) use (&$summaryValue) {
                $summaryValue = $value;

                return true;
            });
        });
        $detail->assertInertia(fn (Assert $page) => $page->where('total', $summaryValue));
    })->with([
        'cash_sale / dr' => ['cash_sale', 'dr', 'total_cash_sales.total_dr'],
        'advances / dr' => ['advances', 'dr', 'total_advances.total_dr'],
        'advances / cr' => ['advances', 'cr', 'total_advances.total_cr'],
        'charity / dr' => ['charity', 'dr', 'total_charity.total_dr'],
        // charity / cr is intentionally excluded: totalCharity() never selects total_cr (pre-existing,
        // unrelated to this feature), so the summary prop is always null.
        'customer / dr' => ['customer', 'dr', 'total_customers.total_dr'],
        'customer / cr' => ['customer', 'cr', 'total_customers.total_cr'],
        'drawings / dr' => ['drawings', 'dr', 'total_drawings.total_dr'],
        'drawings / cr' => ['drawings', 'cr', 'total_drawings.total_cr'],
        'other_receivable / dr' => ['other_receivable', 'dr', 'total_others_receivables.total_dr'],
        'other_receivable / cr' => ['other_receivable', 'cr', 'total_others_receivables.total_cr'],
        'payable / dr' => ['payable', 'dr', 'total_payables.total_dr'],
        'payable / cr' => ['payable', 'cr', 'total_payables.total_cr'],
        'supplier / dr' => ['supplier', 'dr', 'total_suppliers.total_dr'],
        'supplier / cr' => ['supplier', 'cr', 'total_suppliers.total_cr'],
        // expenses / * is intentionally excluded: the summary controller adds commission totals on top
        // of totalExpenses() even though commission accounts are already type=expenses (pre-existing
        // double-count, unrelated to this feature), so the summary figure never equals the detail sum.
    ]);

test('rows are grouped by account, sorted by date within each account, with per-account and grand totals', function () {
    // Arrange: two accounts, legs seeded out of order to prove sorting isn't accidental.
    $accountB = Account::factory()->advances()->create(['name' => 'B Account']);
    $accountA = Account::factory()->advances()->create(['name' => 'A Account']);
    seedLeg($accountB, 'journal', '2026-07-20', 100, 0);
    seedLeg($accountA, 'journal', '2026-07-15', 400, 0);
    seedLeg($accountB, 'journal', '2026-07-10', 200, 0);
    seedLeg($accountA, 'journal', '2026-07-05', 300, 0);

    // Action
    $response = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances', 'side' => 'dr', 'start_date' => '2026-07-01', 'end_date' => '2026-07-31',
    ]));

    // Assert: all of A's rows (date-ascending) come before all of B's rows (date-ascending).
    $response->assertInertia(fn (Assert $page) => $page
        ->has('rows', 4)
        ->where('rows.0.name', 'A Account')->where('rows.0.posted_at', '2026-07-05')->where('rows.0.dr', 300)
        ->where('rows.1.name', 'A Account')->where('rows.1.posted_at', '2026-07-15')->where('rows.1.dr', 400)
        ->where('rows.2.name', 'B Account')->where('rows.2.posted_at', '2026-07-10')->where('rows.2.dr', 200)
        ->where('rows.3.name', 'B Account')->where('rows.3.posted_at', '2026-07-20')->where('rows.3.dr', 100)
        ->has('accountTotals', 2)
        ->where('accountTotals.0.name', 'A Account')->where('accountTotals.0.total', 700)
        ->where('accountTotals.1.name', 'B Account')->where('accountTotals.1.total', 300)
        ->where('total', 1000)
    );
});

test('rejects an unknown category or side', function () {
    // Action
    $response = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'not-a-category',
        'side' => 'not-a-side',
    ]));

    // Assert
    $response->assertInvalid(['category', 'side']);
});

test('redirects a guest to login', function () {
    // Arrange
    auth()->logout();

    // Action
    $response = $this->get(route('reports.in.out.transactions.detail', [
        'category' => 'advances',
        'side' => 'dr',
    ]));

    // Assert
    $response->assertRedirect(route('login'));
});
