<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Accounts\Account;
use App\Models\Accounts\JournalDetail;
use App\Models\Accounts\JournalLedger;
use App\ValueObjects\BalanceInfo;
use Carbon\CarbonInterface;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountService
{
    public function getExpenses(
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        ?string $account = null
    ): Collection {
        $expenses = JournalLedger::query()
            ->select([
                'id',
                'account_id',
                'name',
                'posted_at',
                'city',
                'detail',
                DB::raw('dr as expenses'),
                'cr',
            ])
            ->whereBetween('posted_at', [$startDate, $endDate])
            ->typeExpense()
            ->headJournal()
            ->when($account, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->where(function (Builder $query) {
                $query->where('dr', '>', 0)
                    ->orWhere('cr', '>', 0);
            })
            ->get()->keyBy(fn ($r) => $r->id.'-'.$r->account_id);
        $commissionExpenseAccounts = Account::query()->agents()
            ->whereNotNull('expense_account')
            ->select(['expense_account'])->distinct()->get()->map(fn ($row) => $row->expense_account)->toArray();
        $commission = JournalLedger::query()
            ->with('agentInfo')
            ->select([
                'id',
                'account_id',
                'name',
                'detail',
                'posted_at',
                'city',
                DB::raw('dr as expenses'),
                'cr',
            ])
            ->whereBetween('posted_at', [$startDate, $endDate])
            ->whereIn('account_id', $commissionExpenseAccounts)
            ->typeExpense()
            ->where(function (Builder $query) {
                $query->where('dr', '>', 0)
                    ->orWhere('cr', '>', 0);
            })
            // ->where('dr', '>', 0)
            ->when($account, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->get();
        /** JournalLEdger $row */
        $commission->each(function ($row) use ($expenses) {
            $agent = $row->agentInfo;
            $row->detail .= ', Agent: '.$agent?->name;
            $expenses->push($row);
        });
        $partnershipExpenseAccounts = Account::query()->partners()
            ->whereNotNull('expense_account')
            ->select(['expense_account'])->get()->map(fn ($row) => $row->expense_account)->toArray();

        $partnerships = JournalLedger::query()
            ->select([
                'id',
                'account_id',
                'name',
                'detail',
                'posted_at',
                'city',
                DB::raw('dr as expenses'),
                'cr',
            ])
            ->whereBetween('posted_at', [$startDate, $endDate])
            ->whereIn('account_id', $partnershipExpenseAccounts)
            ->typeExpense()
            ->where(function (Builder $query) {
                $query->where('dr', '>', 0)
                    ->orWhere('cr', '>', 0);
            })
            // ->where('dr', '>', 0)
            ->when($account, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->get();
        $partnerships->each(function ($row) use ($expenses) {
            $expenses->push($row);
        });

        return $expenses->keyBy(fn ($r) => $r->id.'-'.$r->account_id);
    }

    public function getAccountBalance(int $accountId): int
    {
        $balance = JournalDetail::query()
            ->selectRaw('sum(dr) as total_dr')
            ->selectRaw('sum(cr) as total_cr')
            ->where('account_id', $accountId)
            ->first();

        return (int) $balance->total_dr - (int) $balance->total_cr;
    }

    public function getAccountBalanceDetailed(int $accountId): array
    {
        $balance = JournalDetail::query()
            ->selectRaw('sum(dr) as total_dr')
            ->selectRaw('sum(cr) as total_cr')
            ->where('account_id', $accountId)
            ->first();

        return [
            'balance' => (int) $balance->total_dr - (int) $balance->total_cr,
            'debit' => (int) $balance->total_dr,
            'credit' => (int) $balance->total_cr,
        ];
    }

    public function getCashAccount(): Account
    {
        return once(fn () => Account::query()
            ->where('name', 'like', '%'.AccountType::CashAccount->value.'%')
            ->orderBy('id')
            ->sole());
    }

    public function getTotalPaymentReceived(CarbonInterface $startDate, CarbonInterface $endDate)
    {
        return JournalLedger::query()
            ->headJournal()
            ->typeCustomer()
            ->whereBetween('posted_at', [$startDate, $endDate])
            ->sum(DB::raw('cr - dr'));
    }

    public function bankBookSummary(CarbonInterface $date): array
    {
        $accounts = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->postedBefore($date)
            ->groupBy(['account_id'])
            ->get();

        $transactions = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->postedOnAfter($date)
            ->groupBy(['account_id'])
            ->get()->keyBy('account_id');

        $banks = $accounts->map(function ($row) use ($transactions) {
            $debit = $credit = 0;
            $transaction = $transactions[$row['account_id']] ?? [];
            if ($transaction) {
                $debit = $transaction->total_dr;
                $credit = $transaction->total_cr;
            }
            $opening_balance = $row->total_dr - $row->total_cr;
            $closing_balance = $opening_balance + ($debit - $credit);
            $row->debit = $debit;
            $row->credit = $credit;
            $row->opening_balance = $opening_balance;
            $row->closing_balance = $closing_balance;

            return $row;
        });
        $totals = [
            'opening_balance' => $banks->sum('opening_balance'),
            'debit' => $banks->sum('debit'),
            'credit' => $banks->sum('credit'),
            'closing_balance' => $banks->sum('closing_balance'),
        ];

        return [
            'filters' => ['start_date' => $date->toDateString()],
            'rows' => $banks,
            'totals' => $totals,
        ];
    }

    public function getCashSales(CarbonInterface $date): int
    {
        return JournalLedger::query()
            ->salesHead()
            ->where('account_id', $this->getCashAccount()->id)
            ->whereBetween('posted_at', [$date->startOfDay(), $date->endOfDay()])
            ->sum(DB::raw('dr - cr'));
    }

    public function balanceBefore(int $accountId, CarbonInterface $date)
    {
        return JournalLedger::query()
            ->where('account_id', $accountId)
            ->whereDate('posted_at', '<', $date)
            ->sum(DB::raw('dr - cr'));
    }

    public function balanceOn(int $accountId, CarbonInterface|string $date)
    {
        return JournalLedger::query()
            ->where('account_id', $accountId)
            ->whereDate('posted_at', '<=', $date)
            ->sum(DB::raw('dr - cr'));
    }

    public function balanceInfoByDate(int $accountId, string|CarbonInterface $date): BalanceInfo
    {
        $query = JournalLedger::query()
            ->where('account_id', '=', $accountId)
            ->where('posted_at', '<', $date)
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr');

        $balance = $query->first();

        return new BalanceInfo(
            $balance->total_cr ?? 0,
            $balance->total_dr ?? 0,
        );
    }

    public function balanceOnByType(AccountType $type, CarbonInterface $date): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->where('type', $type)
            ->where('posted_at', '<=', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function balanceBeforeByType(AccountType $type, CarbonInterface $date): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->where('type', $type)
            ->where('posted_at', '<', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function getExpenseAccounts(mixed $select = null): Collection
    {
        $query = Account::query()->expenses();
        if ($select) {
            $query->select($select);
        } else {
            $query->select(['id', 'type', 'name']);
        }

        $query->orderBy('name');

        /** @var Collection<int, Account> */
        return $query->get();
    }

    public function getAccountTotal(int $account_id): JournalDetail
    {
        return JournalDetail::query()
            ->selectRaw('sum(dr) as total_debit')
            ->selectRaw('sum(cr) as total_credit')
            ->where('account_id', $account_id)->first();
    }

    public function getAccountOverDueCredit(int $account_id): array
    {
        $total = $this->getAccountTotal($account_id);
        $total_debit = $total->total_credit;
        $history = $this->getAccountOverDueQuery($account_id, $total_debit)
            ->with('journal')
            ->get();
        $journal = $history->map(fn ($row) => [
            'journal_id' => $row->journal_id,
            'balance' => $row->balance,
            'month' => $row->journal->posted_at->format('M, Y'),
            'reference_no' => $row->journal->reference_no,
            'detail' => $row->journal->detail,
            'resource_id' => $row->journal->resource_id,
            'resource_type' => $row->journal->resource_type,
            'head' => $row->journal->head,
        ]);

        return [
            'total_debit' => $total_debit,
            'total_credit' => $total->total_credit,
            'transaction' => $history->first()?->journal,
            'balance' => ($total->total_debit - $total->total_credit),
            'journal' => $journal,
        ];

    }

    /**
     * @return Builder<JournalDetail>
     */
    public function getAccountOverDueQuery(int $account_id, int $total): Builder
    {
        // query: select * from (
        //  select sum(dr) over( order by journal_id) debit, journal_id  from journal_details where account_id = 1 order by journal_id asc
        // ) total where debit > 4916605 limit 1;
        $sum_query = JournalDetail::query()
            ->select(['journal_id', 'created_at'])
            ->selectRaw('sum(dr) over( order by journal_id) debit')
            ->where('account_id', $account_id)
            ->where('dr', '>', 0)
            ->orderBy('journal_id');

        return JournalDetail::query()
            ->select('*')
            ->selectRaw('(debit - '.$total.') as  balance')
            ->fromSub($sum_query->toRawSql(), 'ledger')
            ->where('debit', '>', $total);
    }
}
