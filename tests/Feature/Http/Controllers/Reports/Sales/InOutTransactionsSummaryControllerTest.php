<?php

use App\Actions\Accounts\Period\TransactionBook;
use App\Http\Middleware\AuthRole;
use App\Services\AccountService;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

use function Pest\Laravel\partialMock;

it('loads the In-Out Transactions Summary page', function (): void {
    $admin = $this->getAdmin();
    $this->creatCashAccount();
    $this->withoutExceptionHandling();
    $this->attachPermissions($admin, 'reports.in.out.transactions.summary');

    app()->bind(TransactionBook::class, fn() => new class
    {
        public function totalAdvances(): object
        {
            return $this->totals();
        }

        public function totalCharity(): object
        {
            return $this->totals();
        }

        public function totalDrawings(): object
        {
            return $this->totals();
        }

        public function totalExpenses(): object
        {
            return $this->totals();
        }

        public function totalCommission(): object
        {
            return $this->totals();
        }

        public function totalSuppliers(): object
        {
            return $this->totals();
        }

        public function totalPayables(): object
        {
            return $this->totals();
        }

        public function totalOtherReceivables(): object
        {
            return $this->totals();
        }

        public function totalCustomers(): object
        {
            return $this->totals();
        }

        public function totalCashSales(): object
        {
            return $this->totals();
        }

        public function totalLiability(): object
        {
            return $this->totals();
        }

        public function totals(int $dr = 0, int $cr = 0): object
        {
            return new class($dr, $cr)
            {
                public function __construct(public int $total_dr, public int $total_cr) {}

                public function toArray(): array
                {
                    return [
                        'total_dr' => $this->total_dr,
                        'total_cr' => $this->total_cr,
                    ];
                }
            };
        }
    });
    $cashOpeningBal = 1000;
    $cashClosingBal = 10000;
    $bankOpeningBal = 1000;
    $bankClosingBal = 10000;
    partialMock(AccountService::class,
        function (MockInterface $mock) use ($bankOpeningBal, $bankClosingBal, $cashClosingBal, $cashOpeningBal) {
            $mock->shouldReceive('balanceBefore')->andReturn($cashOpeningBal);
            $mock->shouldReceive('balanceOn')->andReturn($cashClosingBal);
            $mock->shouldReceive('balanceBeforeByType')->andReturn($bankOpeningBal);
            $mock->shouldReceive('balanceOnByType')->andReturn($bankClosingBal);
        });

    $response = $this->withoutMiddleware([
        AuthRole::class,
        EnsureEmailIsVerified::class,
    ])->actingAs($admin)
        ->get(route('reports.in.out.transactions.summary'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/InOutTransactionsSummary')
    );
});
