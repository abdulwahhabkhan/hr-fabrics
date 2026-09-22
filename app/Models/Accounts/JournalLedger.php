<?php

namespace App\Models\Accounts;

use App\Enums\AccountType;
use App\Enums\JournalHead;
use App\Models\Model;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Services\AccountService;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read float $total_dr
 * @property-read float $total_cr
 * @property-read float $debit
 * @property-read float $credit
 */
class JournalLedger extends Model
{
    protected $table = 'journal_ledgers'; // view

    protected $casts = [
        'posted_at' => 'datetime',
        'total_dr' => 'float',
        'total_cr' => 'float',
        'credit_limit' => 'int',
        'is_suspended' => 'int',
    ];

    public function agentInfo(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'id')
            ->where('type', AccountType::Agent->value);
    }

    public function customerPayment(): HasOne
    {
        return $this->hasOne(JournalDetail::class, 'account_id', 'account_id')
            ->where('dr', '>', '0');
    }

    public function latestDebit(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'last_debit_id')->where('dr', '>', 0);
    }

    public function latestCredit(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'last_credit_id')->where('cr', '>', 0);
    }

    #[Scope]
    protected function lastDebit(Builder $query): void
    {
        $query->selectRaw('max(case when dr > 0 then id else 0 end) as last_debit_id')
            ->with('latestDebit');
    }

    #[Scope]
    protected function lastCredit(Builder $query): void
    {
        $query->selectRaw('max(case when cr > 0 then id else 0 end) as last_credit_id')
            ->with('latestCredit');
    }

    #[Scope]
    protected function headJournal(Builder $query): void
    {
        $query->where('head', JournalHead::Journal->value);
    }

    #[Scope]
    protected function typeCustomer(Builder $query): void
    {
        $query->where('type', AccountType::Customer->value);
    }

    #[Scope]
    protected function salesHead(Builder $query): Builder
    {
        return $query->where('head', JournalHead::Sales->value);
    }

    #[Scope]
    protected function typeSupplier(Builder $query): Builder
    {
        return $query->where('type', AccountType::Supplier->value);
    }

    #[Scope]
    protected function typePayables(Builder $query): Builder
    {
        return $query->where('type', AccountType::Payable->value);
    }

    #[Scope]
    protected function typeOtherReceivables(Builder $query): Builder
    {
        return $query->where('type', AccountType::OtherReceivable->value);
    }

    #[Scope]
    protected function cashAccount(Builder $query): Builder
    {
        $cashAccountId = resolve(AccountService::class)->getCashAccount()->id;

        return $query->where('account_id', $cashAccountId);
    }

    protected function getDetailLinkAttribute(): string
    {
        $resource_id = $this->resource_id;
        info($this->resource_type);

        return match ($this->resource_type) {

            Order::morphClass() => route('sales.orders.show', $resource_id),
            SalesReturn::morphClass() => route('sales.returns.show', $resource_id),
            PurchaseReturn::morphClass() => route('purchases.por.show', $resource_id),
            Purchase::morphClass() => route('purchases.pos.show', $resource_id),
            default => route('accounts.journals.show', $this->id),
        };
    }

    #[Scope]
    protected function cashBank(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('type', AccountType::Bank->value)
                ->orWhere('name', '=', AccountType::CashAccount->value);
        });
    }

    #[Scope]
    protected function postedBefore(Builder $query, string $date): Builder
    {
        return $query->where('posted_at', '<', $date);
    }

    #[Scope]
    protected function postedOnBefore(Builder $query, string $date): Builder
    {
        return $query->where('posted_at', '<=', $date);
    }

    #[Scope]
    protected function postedAfter(Builder $query, string $date): Builder
    {
        return $query->where('posted_at', '>', $date);
    }

    #[Scope]
    protected function postedOnAfter(Builder $query, string $date): Builder
    {
        return $query->where('posted_at', '>=', $date);
    }

    #[Scope]
    protected function postedBetween(Builder $query, string $start_date, string $end_date): Builder
    {
        return $query->where(function (Builder $query) use ($start_date, $end_date) {
            $query->whereBetween('posted_at', [$start_date, $end_date]);
        });
    }

    #[Scope]
    protected function typeAdvances(Builder $query): Builder
    {
        return $query->where('type', AccountType::Advances->value);
    }

    #[Scope]
    protected function typeDrawings(Builder $query): Builder
    {
        return $query->where('type', AccountType::Drawings->value);
    }

    #[Scope]
    protected function typeCharity(Builder $query): Builder
    {
        return $query->where('type', AccountType::Charity->value);
    }

    #[Scope]
    protected function cashAccountName(Builder $query): Builder
    {
        return $query->where('name', AccountType::CashAccount->value);
    }

    #[Scope]
    protected function typeExpense(Builder $query): Builder
    {
        return $query->where('type', AccountType::Expenses->value);
    }

    #[Scope]
    protected function typeBank(Builder $query): Builder
    {
        return $query->where('type', AccountType::Bank->value);
    }

    #[Scope]
    protected function typeLiability(Builder $query): Builder
    {
        return $query->where('type', AccountType::Liability->value);
    }

    #[Scope]
    protected function typeReceivable(Builder $query): Builder
    {
        return $query->whereIn('type', AccountType::receivableAccounts());
    }

    #[Scope]
    protected function journalOrSaleHead(Builder $query): Builder
    {
        return $query->whereIn('head', [JournalHead::Journal, JournalHead::Sales]);
    }
}
