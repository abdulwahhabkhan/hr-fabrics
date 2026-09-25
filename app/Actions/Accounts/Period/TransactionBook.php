<?php

namespace App\Actions\Accounts\Period;

use App\Models\Accounts\Account;
use App\Models\Accounts\JournalLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TransactionBook
{
    public function __construct(
        protected ?Carbon $startDate = null,
        protected ?Carbon $endDate = null
    ) {}

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'cash_sale' => 'Cash Sale',
            'advances' => 'Advances',
            'charity' => 'Charity',
            'customer' => 'Customer',
            'drawings' => 'Drawings',
            'expenses' => 'Expenses',
            'other_receivable' => 'Other Receivable',
            'payable' => 'Payable',
            'supplier' => 'Suppliers',
            'liability' => 'Liability',
        ];
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function categoryQuery(string $category): Builder
    {
        return match ($category) {
            'cash_sale' => $this->cashSalesQuery(),
            'advances' => $this->advancesQuery(),
            'charity' => $this->charityQuery(),
            'customer' => $this->customersQuery(),
            'drawings' => $this->drawingsQuery(),
            'expenses' => $this->expensesQuery(),
            'other_receivable' => $this->otherReceivablesQuery(),
            'payable' => $this->payablesQuery(),
            'supplier' => $this->suppliersQuery(),
            'liability' => $this->liabilityQuery(),
            default => throw new InvalidArgumentException("Unknown transaction category [{$category}]."),
        };
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function advancesQuery(): Builder
    {
        return JournalLedger::query()
            ->typeAdvances()
            ->headJournal()
            ->postedBetween($this->startDate?->toDateString(), $this->endDate?->toDateString());
    }

    public function totalAdvances(): ?JournalLedger
    {
        return $this->advancesQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function drawingsQuery(): Builder
    {
        return JournalLedger::query()
            ->typeDrawings()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalDrawings(): ?JournalLedger
    {
        return $this->drawingsQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function charityQuery(): Builder
    {
        return JournalLedger::query()
            ->typeCharity()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalCharity(): ?JournalLedger
    {
        return $this->charityQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function commissionQuery(): Builder
    {
        return JournalLedger::query()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->typeExpense()
            ->whereIn('account_id', $this->commissionAccounts());
    }

    public function totalCommission(): ?JournalLedger
    {
        return $this->commissionQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function expensesQuery(): Builder
    {
        return JournalLedger::query()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->typeExpense();
    }

    public function totalExpenses(): ?JournalLedger
    {
        return $this->expensesQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function suppliersQuery(): Builder
    {
        return JournalLedger::query()
            ->headJournal()
            ->typeSupplier()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalSuppliers(): ?JournalLedger
    {
        return $this->suppliersQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function payablesQuery(): Builder
    {
        return JournalLedger::query()
            ->typePayables()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalPayables(): ?JournalLedger
    {
        return $this->payablesQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function otherReceivablesQuery(): Builder
    {
        return JournalLedger::query()
            ->typeOtherReceivables()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalOtherReceivables(): ?JournalLedger
    {
        return $this->otherReceivablesQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function customersQuery(): Builder
    {
        return JournalLedger::query()
            ->typeCustomer()
            ->headJournal()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalCustomers(): ?JournalLedger
    {
        return $this->customersQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function cashSalesQuery(): Builder
    {
        return JournalLedger::query()
            ->cashAccount()
            ->salesHead()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    /**
     * @return Builder<JournalLedger>
     */
    public function liabilityQuery(): Builder
    {
        return JournalLedger::query()
            ->typeLiability()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()]);
    }

    public function totalLiability(): ?JournalLedger
    {
        return $this->liabilityQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    public function totalCashSales(): ?JournalLedger
    {
        return $this->cashSalesQuery()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->first();
    }

    public function commissionAccounts(): array
    {
        return Account::query()->agents()
            ->whereNotNull('expense_account')
            ->select(['expense_account'])
            ->distinct()
            ->get()
            ->map(fn ($row) => $row->expense_account)->toArray();
    }
}
